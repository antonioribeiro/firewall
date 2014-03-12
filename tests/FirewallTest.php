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

use PragmaRX\Support\Config;
use PragmaRX\Support\Filesystem;
use PragmaRX\Support\CacheManager;

use PragmaRX\Firewall\Repositories\DataRepository;
use PragmaRX\Firewall\Repositories\Firewall\Firewall as FirewallRepository;

use Illuminate\Console\Application;
use Illuminate\Config\FileLoader;
use Illuminate\Config\Repository;
use Illuminate\Http\Request;

class FirewallTest extends PHPUnit_Framework_TestCase {

	/**
	 * Setup resources and dependencies.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->namespace = 'PragmaRX\Firewall';

		$this->rootDir = __DIR__.'/../src/config';

		$this->fileSystem = new Filesystem;

		$this->fileLoader = new FileLoader($this->fileSystem, __DIR__);

		$this->repository = new Repository($this->fileLoader, 'test');

		$this->repository->package($this->namespace, $this->rootDir, $this->namespace);

		$this->config = new Config($this->repository, $this->namespace);

		$firewallModel = $this->config->get('firewall_model');

		$this->cache = m::mock('PragmaRX\Support\CacheManager');

		$this->validIpv4 = '1.1.1.1';
		$this->invalidIpv4 = '1.1.1';

		$this->validIpv6 = '1:1:1:1:1:1:1:1';
		$this->invalidIpv6 = '1:1:1:1:1:::1';

		$this->fileSystem = new Filesystem;

		$this->request = new Request;

		$this->model = m::mock('StdClass');

		$this->cursor = m::mock('StdClass');

		$this->dataRepository = new DataRepository(

										new FirewallRepository($this->model, $this->cache, $this->config),

										$this->config,

										$this->cache,

										$this->fileSystem
									);


		$this->firewall = new Firewall(
			$this->config,
			$this->dataRepository,
			$this->cache,
			$this->fileSystem,
			$this->request
		);
	}

	public function testValidIP()
	{
		// IPv4
		$this->assertTrue($this->firewall->ipIsValid($this->validIpv4));
		$this->assertFalse($this->firewall->ipIsValid($this->invalidIpv4));

		// IPv6
		$this->assertTrue($this->firewall->ipIsValid($this->validIpv6));
		$this->assertFalse($this->firewall->ipIsValid($this->invalidIpv6));
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