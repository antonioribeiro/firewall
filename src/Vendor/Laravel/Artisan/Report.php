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
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * List all ips from all lists.
     *
     * @return mixed
     */
    public function fire()
    {
        $table = new Table($this->output);

        $list = [];

        foreach (app('firewall')->report() as $ip) {
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

        $table->setHeaders(['IP Address', 'Whitelist', 'Blacklist'])->setRows($list);

        $table->render();
    }
}
