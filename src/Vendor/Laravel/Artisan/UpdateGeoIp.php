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
        $type = $this->laravel->firewall->updateGeoIp()
            ? 'info'
            : 'error';

        $this->displayMessages($type, $this->laravel->firewall->getMessages());
    }
}
