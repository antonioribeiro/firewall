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
     * Remove an ip from a list.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->fireCommand('remove', [$this->argument('ip')]);
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
}
