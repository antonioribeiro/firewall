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

class SentenceParser {

	/**
	 * Parse a sentence 
	 * 
	 * @param  string $sentence 
	 * @param  string $prefix   
	 * @param  string $suffix   
	 * @param  string $config   
	 * @return string           
	 */
	public static function parse(&$sentence, &$prefix, &$suffix, $config = null)
	{
		$prefix = '';
		$suffix = '';

		if(is_null($config))
		{
			return false;
		}

		/// This is old and should be done using Regex now, anyone apply? :)

		$i = 0;
		while ($i < strlen($sentence) and isset($config->get('prefix_suffix_delimiters')[$sentence[$i]])) {
			$prefix .= $sentence[$i];
			$i++;
		}

		$i = strlen($sentence)-1;
		while ($i > -1 and isset($config->get('prefix_suffix_delimiters')[$sentence[$i]])) {
			$suffix = $sentence[$i] . $suffix;
			$i--;
		}

		if ($prefix != $config->get('variable_delimiter_prefix')) {
			$sentence = substr($sentence,strlen($prefix));	
		} else {
			$prefix = '';
		}
		
		if ($suffix != $config->get('variable_delimiter_suffix')) {
			$sentence = substr($sentence,0,strlen($sentence)-strlen($suffix));
		} else {
			$suffix = '';
		}
	}

}



