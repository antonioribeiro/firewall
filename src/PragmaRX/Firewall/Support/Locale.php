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

class Locale
{
	private $language;
	private $country;

	/**
	 * Create a new locale instance
	 * 
	 * @param string $language
	 * @param string $country
	 */
	public function __construct($language = null, $country = null) 
	{
		$this->setLocale($language, $country);
	}

	/**
	 * Language getter
	 * 
	 * @return string
	 */
	public function getLanguage()
	{
		return $this->language;
	}

	/**
	 * Language setter
	 * 
	 * @param string $language
	 */
	public function setLanguage($language)
	{
		if ($language == null)
		{
			$language = $this->language; // if everything else fails, english is the last resort
		}

		$this->language = strtolower($language);
	}

	/**
	 * Locale setter
	 * 
	 * @param string $language
	 */
	public function setLocale($language, $country)
	{
		$this->parseLocale($language, $country);

		$this->absorb($language, $country);
	}

	/**
	 * Set the given language and country to current ones
	 * 
	 * @param  string $language 
	 * @param  string $country  
	 * @return void
	 */
	public function absorb($language, $country)
	{
		$this->setLanguage($language);

		$this->setCountry($country);
	}

	/**
	 * Parse a locale string to language and country separately
	 * 
	 * @param  string &$language 
	 * @param  string &$country  
	 * @return void
	 */
	private function parseLocale(&$language, &$country)
	{
		if ($language instanceof Locale)
		{
			$country = $language->getCountry();

			$language = $language->getLanguage();
		}
		else
		if ($language == null || $country == null)
		{
			if ($language == null && $country == null)
			{
				$language = $this->language; // if everything else fails, english is the last resort
				$country = $this->country;   // if everything else fails, english is the last resort
			}
			else
			{
				if ( ! empty($country))
				{
					$language = $country;
					$country = '';
				}

				$locale = preg_split("/(\||\+|_|\*|#|\.|-|:|\\\|\/)/", $language);

				$language = $locale[0];
				$country = isset($locale[1]) 
							? $locale[1] 
							: '';
			}
		}

		$language = strtolower($language);

		$country = strtoupper($country);
	}

	/**
	 * Country getter
	 * 
	 * @return string
	 */
	public function getCountry()
	{
		return $this->country;
	}

	/**
	 * Country setter
	 * 
	 * @param string $country
	 */
	public function setCountry($country)
	{
		if ($country == null)
		{
			$country = $this->country; // if everything else fails, US is the last resort
		}

		$this->country = strtoupper($country);
	}

	/**
	 * Check if give language is the same as the current locale
	 * 
	 * @param  mixed  $language 
	 * @param  mixed  $country  
	 * @return bool
	 */
	public function is($language, $country = null)
	{
		$this->parseLocale($language, $country);

		return    $this->getLanguage() == $language
		       && $this->getCountry() == $country;
	}

	/**
	 * Get locale as text
	 * 
	 * @return string
	 */
	public function getText()
	{
		return $this->getLanguage() . ($this->getCountry() ? '_' . $this->getCountry() : '');
	}

	/**
	 * Make a locale from string or object
	 * 
	 * @param  mixed $locale   
	 * @param  mixed $fallback 
	 * @return Locale
	 */
	public static function make($locale, $fallback = null)
	{
		if (empty($locale) && ! is_null($fallback))
		{
			$locale = Locale::make($fallback);
		}
		else
		if ( ! empty($locale))
		{
			if ( is_string($locale) )
			{
				$locale = new Locale($locale);
			}
		}

		if(empty($locale))
		{
			$locale = new Locale();
		}

		return $locale;
	}
}