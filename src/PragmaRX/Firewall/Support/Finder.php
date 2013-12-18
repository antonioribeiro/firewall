<?php namespace PragmaRX\Firewall\Support;
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

use Symfony\Component\Finder\Finder as SymfonyFinder;

class Finder {

	/**
	 * Create instance of Finder
	 * 
	 * @return Symfony\Component\Finder\Finder
	 */
	public function __construct()
	{
		$this->finder = SymfonyFinder::create();
	}

	/**
	 * Use files selector
	 * 
	 * @return Symfony\Component\Finder\Finder
	 */
	public function files()
	{
		return $this->finder->files();
	}
	
	/**
	 * Use directories selector
	 * 
	 * @return Symfony\Component\Finder\Finder
	 */
	public function directories()
	{
		return $this->finder->directories();
	}

	/**
	 * Tell finder to find on a given path
	 * 
	 * @param  Finder $finder
	 * @return [type]         [description]
	 */
	public function in($path)
	{
		return $this->in($path);
	}

}