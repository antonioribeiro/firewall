<?php

namespace PragmaRX\Firewall\Vendor\Laravel\Artisan;

class UpdateGeoIp extends Base
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'firewall:updategeoip';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the GeoIP database.';

    /**
     * Update the geo ip database.
     *
     * @return void
     */
    public function fire()
    {
        $firewall = app('firewall');

        $type = $firewall->updateGeoIp()
            ? 'info'
            : 'error';

        $this->displayMessages($type, $firewall->getMessages());
    }
}
