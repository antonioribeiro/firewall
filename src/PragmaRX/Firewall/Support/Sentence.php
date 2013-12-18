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

class Sentence {

	/**
	 * Prefix
	 * 
	 * @var string
	 */
	public $prefix;

	/**
	 * Sentence
	 * 
	 * @var string
	 */
	private $sentence;

	/**
	 * Translation sentence
	 * 
	 * @var string
	 */
	protected $translation;

	/**
	 * Translation was found?
	 * 
	 * @var boolean
	 */
	public $translationFound;

	/**
	 * Sentence unique ID
	 * 
	 * @var integer
	 */
	private $id;

	/**
	 * Sentence hash
	 * 
	 * @var string
	 */
	private $hash;

	/**
	 * Sentence domain
	 * 
	 *  This default is the last fallback
	 *  
	 * @var string
	 */
	private $domain = 'messages';

	/**
	 * Sentence Mode (natural or key)
	 * @var PragmaRX\Firewall\Support\Mode
	 */
	private $mode;

	/**
	 * Suffix
	 * 
	 * @var string
	 */
	public $suffix;

	/**
	 * Create a new sentence bag instance.
	 *
	 * @param  array  $sentences
	 * @return void
	 */
	public function __construct($sentence, $domain = null, Mode $mode, $config = null)
	{
		$this->mode = $mode;

		$this->config = $config;

		$this->setSentence($sentence);

		$this->generateDomain($domain);
	}

	/**
	 * Id getter
	 * 
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Id setter
	 * 
	 * @param int $id 
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * Get the current sentence hash
	 * 
	 * @return string
	 */
	public function getHash()
	{
		if ( ! $this->hash)
		{
			$this->hash = $this->calculateHash();
		}

		return $this->hash;
	}

	/**
	 * Calculate the current sentence hash
	 * 
	 * @return string
	 */
	public function calculateHash()
	{
		return $this->hash = SHA1($this->getSentence() . $this->getDomain());
	}

	/**
	 * Sentence domain getter
	 * 
	 * @return string
	 */
	public function getDomain()
	{
		return $this->domain;
	}

	/**
	 * Sentence domain generator
	 * 
	 * @param  string $domain 
	 * @return string         
	 */
	public function generateDomain($domain)
	{
		if($domain)
		{
			$this->setDomain($domain);
		}
		else
		{
			if($this->config)
			{
				$this->setDomain($this->config->get('default_domain'));
			}

			// Otherwise it will use the defaulted one
		}
	}

	/**
	 * Sentence domain setter
	 * 
	 * @param string $domain
	 */
	public function setDomain($domain)
	{
		if ($this->domain !== $domain) 
		{
			$this->domain = $domain;

			$this->calculateHash();
		}
	}

	/**
	 * Get the sentence with prefix and suffix
	 * 
	 * @return string
	 */
	public function getFullSentence()
	{
		return $this->getProperty('sentence', true);
	}

	/**
	 * Get the sentence
	 * 
	 * @param  boolean $full 
	 * @return string
	 */
	public function getSentence($full = false)
	{
		return $this->getProperty('sentence', $full);
	}

	/**
	 * Sentence setter
	 * 
	 * @param string $sentence
	 */
	public function setSentence($sentence)
	{
		preg_match("/^(natural|key)::(.*)/", $sentence, $matches);

		if(count($matches) == 3)
		{
			$this->mode = new Mode($matches[1]);
			$sentence = $matches[2];
		}

		$this->sentence = $sentence;

		SentenceParser::parse($this->sentence, $this->prefix, $this->suffix, $this->config);

		$this->calculateHash();
	}

	/**
	 * Get the translation text with prefix and suffix
	 * 
	 * @return string
	 */
	public function getFullTranslation()
	{
		return $this->getProperty('translation', true);
	}

	/**
	 * Get the translation text
	 * 
	 * @param  boolean $full 
	 * @return string
	 */
	public function getTranslation($full = false)
	{
		return $this->getProperty('translation', $full);
	}

	/**
	 * Translation setter
	 * 
	 * @param string $translation
	 */
	public function setTranslation($translation)
	{
		$this->translation = $translation;
	}

	/**
	 * Mode getter
	 * 
	 * @return string
	 */
	public function getMode()
	{
		return $this->mode->get();
	}

	/**
	 * Mode setter
	 * 
	 * @param string $mode
	 */
	public function setMode($mode)
	{
		$this->mode->set($mode);
	}

	/**
	 * Get object property
	 * 
	 * @param  string $property 
	 * @param  bool $full     
	 * @return string
	 */
	public function getProperty($property, $full)
	{
		return ($full ? $this->prefix : '') . $this->$property . ($full ? $this->suffix : '');
	}

	/**
	 * Make a Sentence object
	 * 
	 * @param  string $message 
	 * @param  string $domain  
	 * @param  string $mode    
	 * @return Sentence
	 */
	public static function make($message, $domain, $mode)
	{
		return new Sentence($message, $domain, $mode);
	}

	/**
	 * Make a translation Sentence object
	 * 
	 * @param  string $message    
	 * @param  string $translation
	 * @param  string $domain     
	 * @param  string $mode       
	 * @return string             
	 */
	public static function makeTranslation($message, $translation, $domain, $mode)
	{
		$sentence = new Sentence($message, $domain, $mode);

		$sentence->translation = $translation;

		return $sentence;
	}

}