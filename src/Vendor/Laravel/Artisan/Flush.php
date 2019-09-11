<?php

namespace PragmaRX\Firewall\Vendor\Laravel\Artisan;

class Flush extends Base
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'firewall:cache:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the firewall cache.';

    /**
     * Clear the list.
     *
     * @return mixed
     */
    public function fire()
    {
        app('firewall')->clear();

        $this->info('Cache cleared.');
    }
}
