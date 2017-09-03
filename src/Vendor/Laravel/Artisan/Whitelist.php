<?php

namespace PragmaRX\Firewall\Vendor\Laravel\Artisan;

class Whitelist extends AddToList
{
    /**
     * Current list name.
     *
     * @var string
     */
    protected $listName = 'whitelist';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'firewall:whitelist';
}
