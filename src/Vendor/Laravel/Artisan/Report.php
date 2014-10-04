<?php namespace PragmaRX\Firewall\Vendor\Laravel\Artisan;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Report extends Base {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'firewall:list';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'List all IP address, white and blacklisted.';

	/**
	 * The table helper set.
	 *
	 * @var \Symfony\Component\Console\Helper\TableHelper
	 */
	protected $table;

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$this->table = $this->getHelperSet()->get('table');

		$list = array();

		foreach ($this->laravel->firewall->report() as $ip)
		{
			$list[] = array(
								$ip['ip_address'], 
								$ip['whitelisted'] == false ? '' : '    X    ', 
								$ip['whitelisted'] == false ? '    X    ' : ''
							);
		}

		$this->table->setHeaders(array('IP Address', 'Whitelist', 'Blacklist'))->setRows($list);
                                                          
		$this->table->render($this->getOutput());		
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
		);
	}

}
