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

use PragmaRX\Firewall\Firewall;
use Symfony\Component\Translation\MessageSelector as SymfonyMessageSelector;
use Symfony\Component\Translation\TranslatorInterface;

class Lang implements TranslatorInterface {

    /**
     * Create a lang instance
     *     
     * @param Firewall $firewall
     */
	public function __construct(Firewall $firewall)
	{
		$this->firewall = $firewall;
	}

    /**
     * Determine if a translation exists.
     *
     * @param  string  $key
     * @param  string  $locale
     * @return bool
     */
    public function has($key, $locale = null)
    {
        return $this->firewall->has($key, $locale);
    }

    /**
     * Get the translation for the given key.
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string  $locale
     * @return string
     */
    public function get($key, array $replace = array(), $locale = null)
    {
        return $this->firewall->get($key, $replace, $locale);
    }

    /**
     * Get a translation according to an integer value.
     *
     * @param  string  $key
     * @param  int     $number
     * @param  array   $replace
     * @param  string  $locale
     * @return string
     */
    public function choice($key, $number, array $replace = array(), $locale = null)
    {
        return $this->firewall->choice($key, $number, $replace, null, $locale);
    }

    /**
     * Translates the given message.
     *
     * @param string      $id         The message, the message key or an object that can be cast to string
     * @param array       $parameters An array of parameters for the message
     * @param string|null $domain     The domain for the message
     * @param string|null $locale     The locale or null to use the default
     *
     * @return string The translated string
     *
     * @api
     */
    public function trans($id, array $parameters = null, $domain = 'messages', $locale = null)
    {
    	return $this->firewall->translate($id, $domain, $locale, $parameters);
    }

    /**
     * Translates the given choice message by choosing a translation according to a number.
     *
     * @param string      $id         The message id (may also be an object that can be cast to string)
     * @param integer     $number     The number to use to find the indice of the message
     * @param array       $parameters An array of parameters for the message
     * @param string|null $domain     The domain for the message or null to use the default
     * @param string|null $locale     The locale or null to use the default
     *
     * @return string The translated string
     *
     * @api
     */
    public function transChoice($id, $number, array $parameters = array(), $domain = null, $locale = null)
    {
    	return $this->choice($id, $number, $parameters, $domain, $locale);
    }

    /**
     * Load the specified language group.
     *
     * @param  string  $namespace
     * @param  string  $group
     * @param  string  $locale
     * @return void
     */
    public function load($namespace, $group, $locale)
    {
        /// It's already loaded :)
    }

    /**
     * Determine if the given group has been loaded.
     *
     * @param  string  $namespace
     * @param  string  $group
     * @param  string  $locale
     * @return bool
     */
    protected function isLoaded($namespace, $group, $locale)
    {
        return true;
    }

    /**
     * Add a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace($namespace, $hint)
    {
        throw new Exception("The Firewall implementation of Lang doesn't support Namespaces.");
    }

    /**
     * Parse a key into namespace, group, and item.
     *
     * @param  string  $key
     * @return array
     */
    public function parseKey($key)
    {
        throw new Exception("The Firewall implementation of Lang doesn't support Namespaces.");
    }

    /**
     * Get the message selector instance.
     *
     * @return \Symfony\Component\Translation\MessageSelector
     */
    public function getSelector()
    {
        return $this->firewall->getSelector();
    }

    /**
     * Set the message selector instance.
     *
     * @param  \Symfony\Component\Translation\MessageSelector  $selector
     * @return void
     */
    public function setSelector(SymfonyMessageSelector $selector)
    {
        $this->firewall->setSelector($selector);
    }

    /**
     * Get the language line loader implementation.
     *
     * @return \Illuminate\Translation\LoaderInterface
     */
    public function getLoader()
    {
        throw new Exception("The Firewall implementation of Lang has no real loader.");
    }


    /**
     * Get the default locale being used.
     *
     * @return string
     */
    public function locale()
    {
        return $this->getLocale();
    }

    /**
     * Returns the current locale.
     *
     * @return string The locale
     *
     * @api
     */
    public function getLocale()
    {
        return $this->firewall->getLocale();
    }

    /**
     * Set the default locale.
     *
     * @param  string  $locale
     * @return void
     */
    public function setLocale($locale)
    {
        $this->firewall->setLocale($locale);
    }

}
