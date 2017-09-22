<?php

namespace PragmaRX\Firewall\Vendor\Laravel\Artisan;

use Symfony\Component\Console\Input\InputOption;

class Clear extends Base
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'firewall:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all ip addresses from white and black lists.';

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
        if (!$this->option('force')) {
            $this->error('This command won\'t run unless you use --force.');
        } else {
            if ($this->laravel->firewall->clear()) {
                $this->info('List cleared.');
            } else {
                $this->info('There were no IP addresses to be deleted.');
            }
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
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
