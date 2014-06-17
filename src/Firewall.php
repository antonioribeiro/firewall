<?php namespace PragmaRX\Firewall;
/**
 * Part of the Firewall package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.  It is also available at
 * the following URL: http://www.opensource.org/licenses/BSD-3-Clause
 *
 * @package    Firewall
 * @version    0.1.0
 * @author     Antonio Carlos Ribeiro @ PragmaRX
 * @license    BSD License (3-clause)
 * @copyright  (c) 2013, PragmaRX
 * @link       http://pragmarx.com
 */

use Exception;

use PragmaRX\Firewall\Database\Migrator;
use PragmaRX\Firewall\Support\Locale;
use PragmaRX\Firewall\Support\SentenceBag;
use PragmaRX\Firewall\Support\Sentence;
use PragmaRX\Firewall\Support\Mode;
use PragmaRX\Firewall\Support\MessageSelector;

use PragmaRX\Support\CacheManager;
use PragmaRX\Support\Config;
use PragmaRX\Support\FileSystem;
use PragmaRX\Support\IpAddress;
use PragmaRX\Support\Response;

use Illuminate\Http\Request;

use PragmaRX\Firewall\Repositories\DataRepository;

class Firewall
{
	private $ip;

	private $config;

	private $cache;

	private $fileSystem;

	private $dataRepository;

	private $messages = array();

	private $request;

	/**
	 * @var Migrator
	 */
	private $migrator;

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
									Migrator $migrator
								)
	{
		$this->config = $config;

		$this->dataRepository = $dataRepository;

		$this->cache = $cache;

		$this->fileSystem = $fileSystem;

		$this->request = $request;

		$this->migrator = $migrator;

		$this->setIp(null);
	}

	public function setIp($ip)
	{
		$this->ip = $ip ?: ($this->ip ?: $this->request->getClientIp());
	}

	public function getIp()
	{
		return $this->ip;
	}

	public function report()
	{
		return $this->dataRepository->firewall->all()->toArray();
	}

	public function whitelist($ip, $force = false)
	{
		return $this->addToList(true, $ip, $force);
	}	

	public function blacklist($ip, $force = false)
	{
		return $this->addToList(false, $ip, $force);
	}

	public function whichList($ip_address)
	{
		$ip_address = $ip_address ?: $this->getIp();

		if( ! $ip_found = $this->dataRepository->firewall->find($ip_address))
		{
			if ( ! $this->config->get('enable_range_search'))
			{
				return;
			}

			foreach($this->dataRepository->firewall->all() as $range)
			{
				if (ipv4_in_range($ip_address, $range->ip_address))
				{
					$ip_found = $range;

					break;
				}
			}

			if ( ! $ip_found)
			{
				return;
			}
		}

		return $ip_found->whitelisted ? 'whitelist' : 'blacklist';
	}

	public function isWhitelisted($ip = null)
	{
		return $this->whichList($ip) == 'whitelist';
	}

	public function isBlacklisted($ip  = null)
	{
		return $this->whichList($ip) == 'blacklist';
	}

	public function ipIsValid($ip)
	{
		try
		{
			return IpAddress::ipV4Valid($ip);
		}
		catch (Exception $e)
		{
			return false;	
		}
	}

	public function addToList($whitelist, $ip, $force)
	{
		$list = $whitelist ? 'whitelist' : 'blacklist';

		$listed = $this->whichList($ip);

		if (! $this->ipIsValid($ip))
		{
			$this->addMessage(sprintf('%s is not a valid IP address', $ip));

			return false;
		}
		else
		if ($listed == $list)
		{
			$this->addMessage(sprintf('%s is already %s', $ip, $list.'ed'));

			return false;
		}
		else
		if ( ! $listed || $force)
		{
			if ($listed)
			{
				$this->remove($ip);
			}

			$this->dataRepository->firewall->addToList($whitelist, $ip);

			$this->addMessage(sprintf('%s is now %s', $ip, $list.'ed'));

			return true;
		}

		$this->addMessage(sprintf('%s is currently %sed', $ip, $listed));

		return false;
	}

	public function remove($ip)
	{
		$listed = $this->whichList($ip);

		if($listed)
		{
			$this->dataRepository->firewall->delete($ip);

			$this->addMessage(sprintf('%s removed from %s', $ip, $listed));

			return true;
		}

		$this->addMessage(sprintf('%s is not listed', $ip));

		return false;
	}

	public function addMessage($message)
	{
		$this->messages[] = $message;
	}

	public function getMessages()
	{
		return $this->messages;
	}

	public function clear()
	{
		return $this->dataRepository->firewall->clear();
	}

    /**
     * Register messages in log
     *
     * @return void
     */ 
    public function log($message)
    {
        if ($this->config->get('enable_log'))
        {
            app()->log->info("Firewall: $message");
        }
    }

    public function blockAccess($content = null, $status = null)
    {
        return Response::make(
	        $content ?: $this->config->get('block_response_message'), 
	        $status ?: $this->config->get('block_response_code')
        );
    }

	public function getMigrator()
	{
		return $this->migrator;
	}
}
