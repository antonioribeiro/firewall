<?php

namespace PragmaRX\Firewall\Tests;

use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;

class ReadIpsFromFilesTest extends TestCase
{
    private function getFilename()
    {
        if (!file_exists($dir = __DIR__.'/files')) {
            mkdir($dir);
        }

        return "{$dir}/iplist.txt";
    }

    public function setup(): void
    {
        parent::setUp();

        $lines =
            "127.0.0.1\n".
            "192.168.17.0/24\n".
            "127.0.0.1/255.255.255.255\n".
            "10.0.0.1-10.0.0.255\n".
            "172.17.*.*\n";

        file_put_contents($this->getFilename(), $lines);

        $this->config('blacklist', $this->getFilename());
    }

    public function testReadFile()
    {
        Firewall::blacklist($this->getFilename());

        $this->assertTrue(Firewall::isBlackListed('10.0.0.9'));
    }
}
