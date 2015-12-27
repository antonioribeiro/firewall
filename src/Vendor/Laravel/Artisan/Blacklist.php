<?php

namespace PragmaRX\Firewall\Vendor\Laravel\Artisan;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Blacklist extends Base
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'firewall:blacklist';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add an IP address to blacklist.';

    /**
     * Create a new command instance.
     *
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire() {
        $type = $this->laravel->firewall->blacklist($this->argument('ip'), $this->option('force'))
            ? 'info'
            : 'error';

        $this->displayMessages($type, $this->laravel->firewall->getMessages());
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments() {
        return [
            ['ip', InputArgument::REQUIRED, 'The IP address to be added.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions() {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Remove IP before adding it to the list.'],
        ];
    }
}
