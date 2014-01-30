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
 * @version    1.0.0
 * @author     Antonio Carlos Ribeiro @ PragmaRX
 * @license    BSD License (3-clause)
 * @copyright  (c) 2013, PragmaRX
 * @link       http://pragmarx.com
 */

use Exception;

use PragmaRX\Firewall\Support\Locale;
use PragmaRX\Firewall\Support\SentenceBag;
use PragmaRX\Firewall\Support\Sentence;
use PragmaRX\Firewall\Support\Mode;
use PragmaRX\Firewall\Support\MessageSelector;

use PragmaRX\Support\CacheManager;
use PragmaRX\Support\Config;
use PragmaRX\Support\FileSystem;

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

	/**
	 * Initialize Firewall object
	 * 
	 * @param Locale $locale
	 */
	public function __construct(
									Config $config, 
									DataRepository $dataRepository,
									CacheManager $cache,
									FileSystem $fileSystem,
									Request $request
								)
	{
		$this->config = $config;

		$this->dataRepository = $dataRepository;

		$this->cache = $cache;

		$this->fileSystem = $fileSystem;

		$this->request = $request;

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
		return $this->dataRepository->firewall->all();
	}

	public function whitelist($ip, $force = false)
	{
		return $this->addToList(true, $ip, $force);
	}	

	public function blacklist($ip, $force = false)
	{
		return $this->addToList(false, $ip, $force);
	}

	public function whichList($ip)
	{
		$ip = $ip ?: $this->getIp();

		if( ! $ip = $this->dataRepository->firewall->find($ip))
		{
			return false;
		}

		return $ip->whitelisted ? 'whitelist' : 'blacklist';
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
		try {
			return inet_pton($ip) !== false;
		} catch (Exception $e) {
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

}
