<?php

namespace PragmaRX\Firewall;

use Exception;
use Illuminate\Http\Request;
use PragmaRX\Firewall\Database\Migrator;
use PragmaRX\Firewall\Repositories\DataRepository;
use PragmaRX\Firewall\Support\AttackBlocker;
use PragmaRX\Firewall\Support\Redirectable;
use PragmaRX\Firewall\Support\Responder;
use PragmaRX\Support\CacheManager;
use PragmaRX\Support\Config;
use PragmaRX\Support\FileSystem;
use PragmaRX\Support\GeoIp\GeoIp;
use PragmaRX\Support\GeoIp\Updater as GeoIpUpdater;
use PragmaRX\Support\IpAddress;

class Firewall
{
    use Redirectable;

    /**
     * The IP adress.
     *
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
    public $geoIp;

    /**
     * The attack blocker.
     *
     * @var AttackBlocker
     */
    private $attackBlocker;

    /**
     * Initialize Firewall object.
     *
     * @param Config         $config
     * @param DataRepository $dataRepository
     * @param CacheManager   $cache
     * @param FileSystem     $fileSystem
     * @param Request        $request
     * @param Migrator       $migrator
     * @param GeoIp          $geoIp
     * @param AttackBlocker  $attackBlocker
     *
     * @internal param AttackBlocker $blocker
     */
    public function __construct(
        Config $config,
        DataRepository $dataRepository,
        CacheManager $cache,
        FileSystem $fileSystem,
        Request $request,
        Migrator $migrator,
        GeoIp $geoIp,
        AttackBlocker $attackBlocker
    ) {
        $this->config = $config;

        $this->dataRepository = $dataRepository;

        $this->cache = $cache;

        $this->fileSystem = $fileSystem;

        $this->request = $request;

        $this->migrator = $migrator;

        $this->geoIp = $geoIp;

        $this->attackBlocker = $attackBlocker;

        $this->setIp(null);
    }

    /**
     * Add a message to the messages list.
     *
     * @param $message
     */
    public function addMessage($message)
    {
        $this->messages[] = $message;
    }

    /**
     * Add an IP to black or whitelist.
     *
     * @param $whitelist
     * @param $ip
     * @param $force
     *
     * @return bool
     */
    public function addToList($whitelist, $ip, $force)
    {
        $list = $whitelist
            ? 'whitelist'
            : 'blacklist';

        if (!$this->ipIsValid($ip)) {
            $this->addMessage(sprintf('%s is not a valid IP address', $ip));

            return false;
        }

        $listed = $this->whichList($ip);

        if ($listed == $list) {
            $this->addMessage(sprintf('%s is already %s', $ip, $list.'ed'));

            return false;
        } else {
            if (!$listed || $force) {
                if ($listed) {
                    $this->remove($ip);
                }

                $this->dataRepository->firewall->addToList($whitelist, $ip);

                $this->addMessage(sprintf('%s is now %s', $ip, $list.'ed'));

                return true;
            }
        }

        $this->addMessage(sprintf('%s is currently %sed', $ip, $listed));

        return false;
    }

    /**
     * Add IP address to sessions list.
     *
     * @param $whitelist
     * @param $ip
     */
    public function addToSessionList($whitelist, $ip)
    {
        $this->dataRepository->firewall->addToSessionList($whitelist, $ip);
    }

    /**
     * Get all IP addresses.
     */
    public function all()
    {
        return $this->dataRepository->firewall->all();
    }

    /**
     * Blacklist an IP adress.
     *
     * @param $ip
     * @param bool $force
     *
     * @return bool
     */
    public function blacklist($ip, $force = false)
    {
        return $this->addToList(false, $ip, $force);
    }

    /**
     * Blacklist an IP adress in the current Session.
     *
     * @param $ip
     *
     * @return bool
     */
    public function blacklistOnSession($ip)
    {
        return $this->addToSessionList(false, $ip);
    }

    /**
     * Create a blocked access response.
     *
     * @return \Illuminate\Http\Response|void
     *
     * @internal param null $content
     * @internal param null $status
     */
    public function blockAccess()
    {
        return (new Responder())->respond(
            $this->config->get('response')
        );
    }

    /**
     * Check if an IP address is in a secondary (black/white) list.
     *
     * @param $ip_address
     *
     * @return bool
     */
    private function checkSecondaryLists($ip_address)
    {
        foreach ($this->dataRepository->firewall->all() as $range) {
            if ($this->hostToIp($range) == $ip_address || $this->ipIsInValidRange($ip_address, $range)) {
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
    public function clear()
    {
        return $this->dataRepository->firewall->clear();
    }

    /**
     * Get country code from an IP address.
     *
     * @param $ip_address
     *
     * @return bool|string
     */
    private function getCountryFromIp($ip_address)
    {
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
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Get list of IP addresses by country.
     *
     * @param $ip_address
     *
     * @return bool|null|object
     */
    private function getListingByCountry($ip_address)
    {
        if (!$this->config->get('enable_country_search')) {
            return false;
        }

        if ($this->validCountry($ip_address)) {
            $country = $ip_address;
        } else {
            if ($country = $this->getCountryFromIp($ip_address)) {
                $country = 'country:'.$country;
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
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Get the migrator object.
     *
     * @return Migrator
     */
    public function getMigrator()
    {
        return $this->migrator;
    }

    /**
     * @param $range
     *
     * @return mixed
     */
    private function hostToIp($range)
    {
        return $this->dataRepository->firewall->hostToIp($range->ip_address);
    }

    /**
     * Check if IP is in a valid range.
     *
     * @param $ip_address
     * @param $range
     *
     * @return bool
     */
    private function ipIsInValidRange($ip_address, $range)
    {
        return $this->config->get('enable_range_search') &&
            IpAddress::ipV4Valid($range->ip_address) &&
            ipv4_in_range($ip_address, $range->ip_address);
    }

    /**
     * Check if IP address is valid.
     *
     * @param $ip
     *
     * @return bool
     */
    public function ipIsValid($ip)
    {
        $ip = $this->dataRepository->firewall->hostToIp($ip);

        try {
            return IpAddress::ipV4Valid($ip) || $this->validCountry($ip);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if IP is blacklisted.
     *
     * @param null $ip
     *
     * @return bool
     */
    public function isBlacklisted($ip = null)
    {
        $list = $this->whichList($ip);

        return !($list == 'whitelist') &&
                $list == 'blacklist';
    }

    /**
     * Check if IP address is whitelisted.
     *
     * @param null $ip
     *
     * @return bool
     */
    public function isWhitelisted($ip = null)
    {
        return $this->whichList($ip) == 'whitelist';
    }

    /**
     * Register messages in log.
     *
     * @return void
     */
    public function log($message)
    {
        if ($this->config->get('enable_log')) {
            app()->log->info("FIREWALL: $message");
        }
    }

    /**
     * Remove IP from all lists.
     *
     * @param $ip
     *
     * @return bool
     */
    public function remove($ip)
    {
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
     *
     * @return bool
     */
    public function removeFromSession($ip)
    {
        return $this->removeFromSessionList($ip);
    }

    /**
     * Remove ip address from sessions list.
     *
     * @param $ip
     */
    private function removeFromSessionList($ip)
    {
        $this->dataRepository->firewall->removeFromSessionList($ip);
    }

    /**
     * Get the list of all IP addresses stored.
     *
     * @return mixed
     */
    public function report()
    {
        return $this->dataRepository->firewall->all()->toArray();
    }

    /**
     * Set the current IP address.
     *
     * @param $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip
            ?: ($this->ip
                ?: $this->request->getClientIp());
    }

    /**
     * Check if a string is a valid country info.
     *
     * @param $country
     *
     * @return bool
     */
    private function validCountry($country)
    {
        $country = strtolower($country);

        if ($this->config->get('enable_country_search')) {
            if (starts_with($country, 'country:') && $this->dataRepository->countries->isValid($country)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tell in which list (black/white) an IP address is.
     *
     * @param $ip_address
     *
     * @return bool|string
     */
    public function whichList($ip_address)
    {
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
     *
     * @return bool
     */
    public function whitelist($ip, $force = false)
    {
        return $this->addToList(true, $ip, $force);
    }

    /**
     * Whitelist an IP adress in the current Session.
     *
     * @param $ip
     *
     * @return bool
     */
    public function whitelistOnSession($ip)
    {
        return $this->addToSessionList(true, $ip);
    }

    /**
     * Update the GeoIp2 database.
     *
     * @return bool
     */
    public function updateGeoIp()
    {
        $success = ($updater = new GeoIpUpdater())->updateGeoIpFiles($this->config->get('geoip_database_path'));

        $this->messages = $updater->getMessages();

        return $success;
    }

    /**
     * Check if the application is receiving some sort of attack.
     *
     * @return bool
     */
    public function isBeingAttacked($ipAddress = null)
    {
        return $this->attackBlocker->isBeingAttacked($ipAddress ?: $this->ip);
    }
}
