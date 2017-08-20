<?php

namespace PragmaRX\Firewall\Vendor\Laravel\Artisan;

use Symfony\Component\Console\Input\InputArgument;

class Remove extends Base
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'firewall:remove';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove an IP address from white or black list.';

    /**
     * Create a new command instance.
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
        $type = $this->laravel->firewall->remove($this->argument('ip'))
            ? 'info'
            : 'error';

        $this->displayMessages($type, $this->laravel->firewall->getMessages());
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
        ];
    }
}
