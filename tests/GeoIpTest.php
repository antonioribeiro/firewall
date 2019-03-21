<?php

namespace PragmaRX\Firewall\Tests;

use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;

class GeoIpTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Firewall::updateGeoIp();

        Firewall::clear();
    }

    public function testGetCountry()
    {
        $this->assertEquals('us', Firewall::getCountryFromIp('8.8.8.8'));

        $this->assertEquals('br', Firewall::getCountryFromIp('200.222.0.24'));
    }

    public function testBlockPerCountry()
    {
        Firewall::blacklist('country:us');

        $this->assertTrue(Firewall::isBlacklisted('8.8.8.8'));

        $this->assertFalse(Firewall::isWhitelisted('8.8.8.8'));
    }

    public function testMakeCountry()
    {
        $this->assertEquals('country:br', Firewall::makeCountryFromString('br'));

        $this->assertEquals('country:br', Firewall::makeCountryFromString('country:br'));

        $this->assertEquals('country:br', Firewall::makeCountryFromString('200.222.0.21'));
    }

    public function testCountryIpListing()
    {
        Firewall::blacklist('8.8.8.7');
        Firewall::blacklist('8.8.8.8');
        Firewall::blacklist('8.8.8.9');

        Firewall::blacklist('200.222.0.21');
        Firewall::blacklist('200.222.0.22');

        $this->assertCount(2, Firewall::allByCountry('br'));

        $this->assertCount(3, Firewall::allByCountry('us'));
    }

    public function testCountryIsValid()
    {
        $this->assertTrue(Firewall::validCountry('country:us'));

        $this->assertTrue(Firewall::validCountry('country:br'));

        $this->assertFalse(Firewall::validCountry('country:xx'));
    }

    public function testCountryCidr()
    {
        Firewall::blacklist('country:us');

        $this->assertTrue(Firewall::isBlacklisted('8.8.8.0/24'));
    }
}
