<?php

namespace PragmaRX\Firewall\Vendor\Laravel\Artisan;

use Symfony\Component\Console\Helper\Table;

class Report extends Base
{
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
        $this->table = new Table($this->output);

        $list = [];

        foreach ($this->laravel->firewall->report() as $ip) {
            $list[] = [
                $ip['ip_address'],
                $ip['whitelisted'] == false
                    ? ''
                    : '    X    ',
                $ip['whitelisted'] == false
                    ? '    X    '
                    : '',
            ];
        }

        $this->table->setHeaders(['IP Address', 'Whitelist', 'Blacklist'])->setRows($list);

        $this->table->render();
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments() {
        return [
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions() {
        return [
        ];
    }
}
