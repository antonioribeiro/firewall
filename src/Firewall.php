<?php

namespace PragmaRX\Firewall;

use Illuminate\Http\Request;
use PragmaRX\Firewall\Repositories\DataRepository;
use PragmaRX\Firewall\Repositories\Message;
use PragmaRX\Firewall\Support\AttackBlocker;
use PragmaRX\Firewall\Support\Responder;
use PragmaRX\Support\Config;
use PragmaRX\Support\GeoIp\Updater as GeoIpUpdater;

class Firewall
{
    /**
     * The IP address.
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
     * The data repository object.
     *
     * @var DataRepository
     */
    private $dataRepository;

    /**
     * The request.
     *
     * @var Request
     */
    private $request;

    /**
     * The attack blocker.
     *
     * @var AttackBlocker
     */
    private $attackBlocker;
    /**
     * @var Message
     */
    private $messageRepository;

    /**
     * Initialize Firewall object.
     *
     * @param Config         $config
     * @param DataRepository $dataRepository
     * @param Request        $request
     * @param AttackBlocker  $attackBlocker
     * @param Message        $messageRepository
     */
    public function __construct(
        Config $config,
        DataRepository $dataRepository,
        Request $request,
        AttackBlocker $attackBlocker,
        Message $messageRepository
    ) {
        $this->config = $config;

        $this->dataRepository = $dataRepository;

        $this->request = $request;

        $this->attackBlocker = $attackBlocker;

        $this->messageRepository = $messageRepository;

        $this->setIp(null);
    }

    /**
     * Get all IP addresses.
     *
     * @return \Illuminate\Support\Collection
     */
    public function all()
    {
        return $this->dataRepository->all();
    }

    /**
     * Get all IP addresses by country.
     *
     * @param $country
     *
     * @return \Illuminate\Support\Collection
     */
    public function allByCountry($country)
    {
        return $this->dataRepository->allByCountry($country);
    }

    /**
     * Blacklist an IP address.
     *
     * @param $ip
     * @param bool $force
     *
     * @return bool
     */
    public function blacklist($ip, $force = false)
    {
        return $this->dataRepository->addToList(false, $ip, $force);
    }

    /**
     * Create a blocked access response.
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|null
     */
    public function blockAccess()
    {
        return (new Responder())->respond(
            $this->config->get('responses.blacklist')
        );
    }

    /**
     * Clear firewall table.
     *
     * @return mixed
     */
    public function clear()
    {
        return $this->dataRepository->clear();
    }

    /**
     * Find an IP address.
     *
     * @param string $ip
     *
     * @return mixed
     */
    public function find($ip)
    {
        return $this->dataRepository->find($ip);
    }

    /**
     * Get the IP address.
     *
     * @param null $ip
     *
     * @return null|string
     */
    public function getIp($ip = null)
    {
        return $ip ?: $this->ip;
    }

    /**
     * Get the messages.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getMessages()
    {
        return $this->messageRepository->getMessages();
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
        return $this->dataRepository->ipIsValid($ip);
    }

    /**
     * Check if IP is blacklisted.
     *
     * @param null|string $ip
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
     * @param null|string $ip
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
     * @param $message
     *
     * @return void
     */
    public function log($message)
    {
        if ($this->config->get('enable_log')) {
            $this->getLogger()->info("FIREWALL: $message");
        }
    }

    public function getLogger()
    {
        if ($stack = $this->config->get('log_stack')) {
            return app()->log->stack($stack);
        }

        return app()->log;
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
        return $this->dataRepository->remove($ip);
    }

    /**
     * Get the list of all IP addresses stored.
     *
     * @return mixed
     */
    public function report()
    {
        return $this->dataRepository->all();
    }

    /**
     * Set the current IP address.
     *
     * @param $ip
     */
    public function setIp($ip)
    {
        if ($ip) {
            $this->ip = $ip;
        } elseif (!$this->ip) {
            if ($ip = $this->request->server('HTTP_CF_CONNECTING_IP')) {
                $this->ip = $ip;
            } elseif ($ip = $this->request->server->get('HTTP_X_FORWARDED_FOR')) {
                $this->ip = $ip;
            } elseif ($ip = $this->request->getClientIp()) {
                $this->ip = $ip;
            }
        }
    }

    /**
     * Check if a string is a valid country info.
     *
     * @param $country
     *
     * @return bool
     */
    public function validCountry($country)
    {
        return $this->dataRepository->validCountry($country);
    }

    /**
     * Tell in which list (black/white) an IP address is.
     *
     * @param $ip
     *
     * @return bool|string
     */
    public function whichList($ip)
    {
        return $this->dataRepository->whichList($this->getIp($ip));
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
        return $this->dataRepository->addToList(true, $ip, $force);
    }

    /**
     * Update the GeoIp2 database.
     *
     * @return bool
     */
    public function updateGeoIp()
    {
        $updater = new GeoIpUpdater();

        $success = $updater->updateGeoIpFiles($this->config->get('geoip_database_path'));

        $this->messageRepository->addMessage($updater->getMessages());

        return $success;
    }

    /**
     * Check if the application is receiving some sort of attack.
     *
     * @param null $ipAddress
     *
     * @return bool
     */
    public function isBeingAttacked($ipAddress = null)
    {
        return $this->attackBlocker->isBeingAttacked($this->getIp($ipAddress));
    }

    /**
     * Get a response to the attack.
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|null
     */
    public function responseToAttack()
    {
        return $this->attackBlocker->responseToAttack();
    }

    /**
     * Get country code from an IP address.
     *
     * @param $ip
     *
     * @return bool|string
     */
    public function getCountryFromIp($ip)
    {
        return $this->dataRepository->getCountryFromIp($ip);
    }

    /**
     * Make a country info from a string.
     *
     * @param $country
     *
     * @return bool|string
     */
    public function makeCountryFromString($country)
    {
        return $this->dataRepository->makeCountryFromString($country);
    }

    /**
     * Get the GeoIP instance.
     *
     * @return object
     */
    public function getGeoIp()
    {
        return $this->dataRepository->getGeoIp();
    }
}
