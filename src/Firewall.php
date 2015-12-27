<?php

namespace PragmaRX\Firewall;

use Exception;
use Illuminate\Http\Request;
use PragmaRX\Support\Config;
use PragmaRX\Support\Response;
use PragmaRX\Support\IpAddress;
use PragmaRX\Support\FileSystem;
use PragmaRX\Support\GeoIp\GeoIp;
use PragmaRX\Support\CacheManager;
use PragmaRX\Firewall\Support\Mode;
use PragmaRX\Firewall\Support\Locale;
use PragmaRX\Firewall\Support\Sentence;
use PragmaRX\Firewall\Database\Migrator;
use PragmaRX\Firewall\Support\SentenceBag;
use PragmaRX\Firewall\Support\MessageSelector;
use PragmaRX\Firewall\Repositories\DataRepository;

class Firewall
{
    private $ip;

    private $config;

    private $cache;

    private $fileSystem;

    private $dataRepository;

    private $messages = [];

    private $request;

    /**
     * @var Migrator
     */
    private $migrator;

    private $geoIp;

    /**
     * Initialize Firewall object
     *
     * @param \PragmaRX\Support\Config $config
     * @param Repositories\DataRepository $dataRepository
     * @param \PragmaRX\Support\CacheManager $cache
     * @param \PragmaRX\Support\FileSystem $fileSystem
     * @param \Illuminate\Http\Request $request
     * @param Database\Migrator $migrator
     * @internal param \PragmaRX\Firewall\Support\Locale $locale
     */
    public function __construct(
        Config $config,
        DataRepository $dataRepository,
        CacheManager $cache,
        FileSystem $fileSystem,
        Request $request,
        Migrator $migrator,
        GeoIp $geoIp
    ) {
        $this->config = $config;

        $this->dataRepository = $dataRepository;

        $this->cache = $cache;

        $this->fileSystem = $fileSystem;

        $this->request = $request;

        $this->migrator = $migrator;

        $this->geoIp = $geoIp;

        $this->setIp(null);
    }

    public function addMessage($message) {
        $this->messages[] = $message;
    }

    public function addToList($whitelist, $ip, $force) {
        $list = $whitelist
            ? 'whitelist'
            : 'blacklist';

        if (!$this->ipIsValid($ip)) {
            $this->addMessage(sprintf('%s is not a valid IP address', $ip));

            return false;
        }

        $listed = $this->whichList($ip);

        if ($listed == $list) {
            $this->addMessage(sprintf('%s is already %s', $ip, $list . 'ed'));

            return false;
        }
        else {
            if (!$listed || $force) {
                if ($listed) {
                    $this->remove($ip);
                }

                $this->dataRepository->firewall->addToList($whitelist, $ip);

                $this->addMessage(sprintf('%s is now %s', $ip, $list . 'ed'));

                return true;
            }
        }

        $this->addMessage(sprintf('%s is currently %sed', $ip, $listed));

        return false;
    }

    public function blacklist($ip, $force = false) {
        return $this->addToList(false, $ip, $force);
    }

    public function blockAccess($content = null, $status = null) {
        if ($this->config->get('block_response_abort')) {
            return abort(
                $this->config->get('block_response_code'),
                $content
                    ?: $this->config->get('block_response_message')
            );
        }

        return Response::make(
            $content
                ?: $this->config->get('block_response_message'),
            $status
                ?: $this->config->get('block_response_code')
        );
    }

    private function checkSecondaryLists($ip_address) {
        if (!$this->config->get('enable_range_search')) {
            return false;
        }

        foreach ($this->dataRepository->firewall->all() as $range) {
            if (
                IpAddress::ipV4Valid($range['ip_address']) &&
                ipv4_in_range($ip_address, $range['ip_address'])
            ) {
                return $range;
            }
        }

        return false;
    }

    public function clear() {
        return $this->dataRepository->firewall->clear();
    }

    private function getCountryFromIp($ip_address) {
        if ($geo = $this->geoIp->searchAddr($ip_address)) {
            return strtolower($geo['country_code']);
        }

        return false;
    }

    public function getIp() {
        return $this->ip;
    }

    private function getListingByCountry($ip_address) {
        if (!$this->config->get('enable_country_search')) {
            return false;
        }

        if ($this->validCountry($ip_address)) {
            $country = $ip_address;
        }
        else {
            if ($country = $this->getCountryFromIp($ip_address)) {
                $country = 'country:' . $country;
            }
        }

        if ($country && $ip_found = $this->dataRepository->firewall->find($country)) {
            return $ip_found;
        }

        return false;
    }

    public function getMessages() {
        return $this->messages;
    }

    public function getMigrator() {
        return $this->migrator;
    }

    public function ipIsValid($ip) {
        try {
            return IpAddress::ipV4Valid($ip) || $this->validCountry($ip);
        }
        catch (Exception $e) {
            return false;
        }
    }

    public function isBlacklisted($ip = null) {
        $list = $this->whichList($ip);

        return !$list == 'whitelist' &&
        $list == 'blacklist';
    }

    public function isWhitelisted($ip = null) {
        return $this->whichList($ip) == 'whitelist';
    }

    /**
     * Register messages in log
     *
     * @return void
     */
    public function log($message) {
        if ($this->config->get('enable_log')) {
            app()->log->info("Firewall: $message");
        }
    }

    public function remove($ip) {
        $listed = $this->whichList($ip);

        if ($listed) {
            $this->dataRepository->firewall->delete($ip);

            $this->addMessage(sprintf('%s removed from %s', $ip, $listed));

            return true;
        }

        $this->addMessage(sprintf('%s is not listed', $ip));

        return false;
    }

    public function report() {
        return $this->dataRepository->firewall->all()->toArray();
    }

    public function setIp($ip) {
        $this->ip = $ip
            ?: ($this->ip
                ?: $this->request->getClientIp());
    }

    private function validCountry($command) {
        $command = strtolower($command);

        if ($this->config->get('enable_country_search')) {
            if (starts_with($command, 'country:')) {
                return true;
            }
        }

        return false;
    }

    public function whichList($ip_address) {
        $ip_address = $ip_address
            ?: $this->getIp();

        if (!$ip_found = $this->dataRepository->firewall->find($ip_address)) {
            if (!$ip_found = $this->getListingByCountry($ip_address)) {
                if (!$ip_found = $this->checkSecondaryLists($ip_address)) {
                    return false;
                }
            }
        }

        if ($ip_found) {
            return $ip_found['whitelisted']
                ? 'whitelist'
                : 'blacklist';
        }

        return false;
    }

    public function whitelist($ip, $force = false) {
        return $this->addToList(true, $ip, $force);
    }
}
