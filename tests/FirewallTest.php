<?php

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

use Mockery as m;

use PragmaRX\Firewall\Firewall;

use PragmaRX\Firewall\Support\Config;
use PragmaRX\Firewall\Support\Filesystem;
use PragmaRX\Firewall\Support\CacheManager;

use PragmaRX\Firewall\Repositories\DataRepository;

use PragmaRX\Firewall\Repositories\Firewall\Firewall as FirewallRepository;

// use PragmaRX\Firewall\Support\Sentence;
// use PragmaRX\Firewall\Support\Locale;
// use PragmaRX\Firewall\Support\SentenceBag;
// use PragmaRX\Firewall\Support\Mode;
// use PragmaRX\Firewall\Support\MessageSelector;

// use PragmaRX\Firewall\Repositories\DataRepository;
// use PragmaRX\Firewall\Repositories\Messages\Laravel\Message;
// use PragmaRX\Firewall\Repositories\Cache\Cache;

use Illuminate\Console\Application;

class FirewallTest extends PHPUnit_Framework_TestCase {

	/**
	 * Setup resources and dependencies.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->config = new Config(new Filesystem);

		$firewallModel = $this->config->get('firewall_model');

		$this->cache = m::mock('PragmaRX\Firewall\Support\CacheManager');

		$this->validIpv4 = '1.1.1.1';
		$this->invalidIpv4 = '1.1.1';

		$this->validIpv6 = '1:1:1:1:1:1:1:1';
		$this->invalidIpv6 = '1:1:1:1:1:::1';

		$this->fileSystem = new Filesystem;

		$this->model = m::mock('StdClass');

		$this->cursor = m::mock('StdClass');

		$this->dataRepository = new DataRepository(

										new FirewallRepository($this->model, $this->cache),

										$this->config,

										$this->cache,

										$this->fileSystem
									);


		$this->firewall = new Firewall(
			$this->config,
			$this->dataRepository,
			$this->cache,
			$this->fileSystem
		);
	}

	public function testValidIP()
	{
		// IPv4
		$this->assertTrue($this->firewall->isValid($this->validIpv4));
		$this->assertFalse($this->firewall->isValid($this->invalidIpv4));

		// IPv6
		$this->assertTrue($this->firewall->isValid($this->validIpv6));
		$this->assertFalse($this->firewall->isValid($this->invalidIpv6));
	}

	public function testReport()
	{
		$this->model->shouldReceive('all')->andReturn($this->cursor);
		$this->cursor->shouldReceive('toArray')->andReturn(array());

		$this->assertEquals($this->firewall->report(), array());
	}

	public function testWhitelist()
	{
		// $this->cache->shouldReceive('has')->andReturn(true);
		// $this->cache->shouldReceive('get')->andReturn(true);
		// $this->assertEquals($this->firewall->whitelist($this->validIpv4), true);
	}

}