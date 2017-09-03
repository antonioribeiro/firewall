<?php

namespace PragmaRX\Firewall\Vendor\Laravel\Artisan;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

abstract class AddToList extends Base
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add an IP address to %s.';

    public function __construct()
    {
        parent::__construct();

        $this->description = sprintf($this->description, $this->listName);
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->fireCommand($this->listName, [$this->argument('ip'), $this->option('force')]);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['ip', InputArgument::REQUIRED, 'The IP address to be added.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Remove IP before adding it to the list.'],
        ];
    }
}
