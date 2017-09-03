<?php

namespace PragmaRX\Firewall\Vendor\Laravel\Artisan;

class Blacklist extends AddToList
{
    /**
     * Current list name.
     *
     * @var string
     */
    protected $listName = 'blacklist';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'firewall:blacklist';
}
