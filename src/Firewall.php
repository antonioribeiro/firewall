<?php

namespace PragmaRX\Firewall;

use Exception;
use Illuminate\Http\Request;
use PragmaRX\Firewall\Support\Redirectable;
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
    use Redirectable;

    /**
     * The IP adress.
     * @var
     */
    private $ip;

    /**
     * The config object.
     *
     * @var Config
     */
    private $config;

    /**
     * The cache manager objetc.
     *
     * @var CacheManager
     */
    private $cache;

    /**
     * The file system object.
     *
     * @var FileSystem
     */
    private $fileSystem;

    /**
     * The data repository object.
     *
     * @var DataRepository
     */
    private $dataRepository;

    /**
     * Saved messages.
     *
     * @var array
     */
    private $messages = [];

    /**
     * The request.
     *
     * @var Request
     */
    private $request;

    /**
     * The database migrator object.
     *
     * @var Migrator
     */
    private $migrator;

    /**
     * The geop ip object.
     *
     * @var GeoIp
     */
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

    /**
     * Add a message to the messages list.
     *
     * @param $message
     */
    public function addMessage($message) {
        $this->messages[] = $message;
    }

    /**
     * Add an IP to black or whitelist.
     *
     * @param $whitelist
     * @param $ip
     * @param $force
     * @return bool
     */
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

    public function addToSessionList($whitelist, $ip)
    {
        $this->dataRepository->firewall->addToSessionList($whitelist, $ip);
    }

    /**
     * Get all IP addresses.
     */
    public function all() {
        return $this->dataRepository->firewall->all();
    }

    /**
     * Blacklist an IP adress.
     *
     * @param $ip
     * @param bool $force
     * @return bool
     */
    public function blacklist($ip, $force = false) {
        return $this->addToList(false, $ip, $force);
    }

    /**
     * Blacklist an IP adress in the current Session.
     *
     * @param $ip
     * @return bool
     */
    public function blacklistOnSession($ip) {
        return $this->addToSessionList(false, $ip);
    }

    /**
     * Create a blocked access response.
     *
     * @param null $content
     * @param null $status
     * @return \Illuminate\Http\Response|void
     */
    public function blockAccess($content = null, $status = null) {
        if ($page = $this->config->get('block_response_page')) {
            return $this->redirectTo($page);
        }

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

    /**
     * Check if an IP address is in a secondary (black/white) list.
     *
     * @param $ip_address
     * @return bool
     */
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

    /**
     * Clear firewall table.
     *
     * @return mixed
     */
    public function clear() {
        return $this->dataRepository->firewall->clear();
    }

    /**
     * Get country code from an IP address.
     *
     * @param $ip_address
     * @return bool|string
     */
    private function getCountryFromIp($ip_address) {
        if ($geo = $this->geoIp->searchAddr($ip_address)) {
            return strtolower($geo['country_code']);
        }

        return false;
    }

    /**
     * Get the IP address.
     *
     * @return mixed
     */
    public function getIp() {
        return $this->ip;
    }

    /**
     * Get list of IP addresses by country.
     *
     * @param $ip_address
     * @return bool|null|object
     */
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

    /**
     * Get the messages.
     *
     * @return array
     */
    public function getMessages() {
        return $this->messages;
    }

    /**
     * Get the migrator object.
     *
     * @return Migrator
     */
    public function getMigrator() {
        return $this->migrator;
    }

    /**
     * Check if IP address is valid.
     *
     * @param $ip
     * @return bool
     */
    public function ipIsValid($ip) {
        try {
            return IpAddress::ipV4Valid($ip) || $this->validCountry($ip);
        }
        catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if IP is blacklisted.
     *
     * @param null $ip
     * @return bool
     */
    public function isBlacklisted($ip = null) {
        $list = $this->whichList($ip);

        return !($list == 'whitelist') &&
                $list == 'blacklist';
    }

    /**
     * Check if IP address is whitelisted.
     *
     * @param null $ip
     * @return bool
     */
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

    /**
     * Remove IP from all lists.
     *
     * @param $ip
     * @return bool
     */
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

    /**
     * Remove IP from all lists.
     *
     * @param $ip
     * @return bool
     */
    public function removeFromSession($ip)
    {
        return $this->removeFromSessionList($ip);
    }

    private function removeFromSessionList($ip)
    {
        $this->dataRepository->firewall->removeFromSessionList($ip);
    }

    /**
     * Get the list of all IP addresses stored.
     *
     * @return mixed
     */
    public function report() {
        return $this->dataRepository->firewall->all()->toArray();
    }

    /**
     * Set the current IP address.
     *
     * @param $ip
     */
    public function setIp($ip) {
        $this->ip = $ip
            ?: ($this->ip
                ?: $this->request->getClientIp());
    }

    /**
     * Check if a string is a valid country info.
     *
     * @param $command
     * @return bool
     */
    private function validCountry($command) {
        $command = strtolower($command);

        if ($this->config->get('enable_country_search')) {
            if (starts_with($command, 'country:')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tell in which list (black/white) an IP address is.
     *
     * @param $ip_address
     * @return bool|string
     */
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

    /**
     * Whitelist an IP address.
     *
     * @param $ip
     * @param bool $force
     * @return bool
     */
    public function whitelist($ip, $force = false) {
        return $this->addToList(true, $ip, $force);
    }

    /**
     * Whitelist an IP adress in the current Session.
     *
     * @param $ip
     * @return bool
     */
    public function whitelistOnSession($ip) {
        return $this->addToSessionList(true, $ip);
    }
}
