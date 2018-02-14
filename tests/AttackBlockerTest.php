<?php

namespace PragmaRX\Firewall\Tests;

use Illuminate\Http\Response;
use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;

class AttackBlockerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->config('notifications.enabled', true);

        $this->config('attack_blocker.allowed_frequency.ip.requests', 2);

        $this->config('attack_blocker.enabled.ip', true);

        $this->config('attack_blocker.enabled.country', true);

        $this->config('attack_blocker.allowed_frequency.country.requests', 3);

        config()->set('services.slack.webhook_url', '12345');
    }

<<<<<<< Updated upstream
    public function testAttack()
=======

    public function test_attack()
>>>>>>> Stashed changes
    {
        $this->config('notifications.enabled', false);

        $this->config('attack_blocker.allowed_frequency.ip.requests', 2);

        $this->assertFalse(Firewall::isBeingAttacked('172.17.0.1'));
        $this->assertFalse(Firewall::isBeingAttacked('172.17.0.1'));

        $this->assertNull(Firewall::responseToAttack());

        $this->assertTrue(Firewall::isBeingAttacked('172.17.0.1'));

        $this->assertInstanceOf(Response::class, Firewall::responseToAttack());
    }

<<<<<<< Updated upstream
    public function testSendNotificationIpAttack()
=======
    public function test_send_notification_ip_attack()
>>>>>>> Stashed changes
    {
        $this->assertFalse(Firewall::isBeingAttacked('8.8.8.8'));
        $this->assertFalse(Firewall::isBeingAttacked('8.8.8.8'));
        $this->assertTrue(Firewall::isBeingAttacked('8.8.8.8'));
    }

<<<<<<< Updated upstream
    public function testSendNotificationCountryAttack()
=======
    public function test_send_notification_country_attack()
>>>>>>> Stashed changes
    {
        Firewall::isBeingAttacked('8.8.8.1');
        Firewall::isBeingAttacked('8.8.8.2');
        Firewall::isBeingAttacked('8.8.8.3');
        Firewall::isBeingAttacked('8.8.8.4');
        Firewall::isBeingAttacked('8.8.8.5');
        Firewall::isBeingAttacked('8.8.8.6');
        Firewall::isBeingAttacked('8.8.8.7');

        $this->assertTrue(Firewall::isBeingAttacked('8.8.8.8'));

        $this->assertFalse(Firewall::isBlacklisted('8.8.8.8'));
    }

<<<<<<< Updated upstream
    public function testSendNotificationCountryBlockAttackAndBlacklist()
=======
    public function test_send_notification_country_block_attack_and_blacklist()
>>>>>>> Stashed changes
    {
        $this->config('attack_blocker.action.blacklist_unknown', true);

        $this->config('attack_blocker.action.blacklist_whitelisted', true);

        Firewall::isBeingAttacked('8.8.8.1');
        Firewall::isBeingAttacked('8.8.8.2');
        Firewall::isBeingAttacked('8.8.8.3');
        Firewall::isBeingAttacked('8.8.8.4');
        Firewall::isBeingAttacked('8.8.8.5');
        Firewall::isBeingAttacked('8.8.8.6');
        Firewall::isBeingAttacked('8.8.8.7');

        $this->assertTrue(Firewall::isBeingAttacked('8.8.8.8'));

        $this->assertFalse(Firewall::isBlacklisted('country:us'));
    }

<<<<<<< Updated upstream
    public function testSendNotificationNoCountryIpAttack()
=======
    public function test_send_notification_no_country_ip_attack()
>>>>>>> Stashed changes
    {
        Firewall::isBeingAttacked('127.0.0.1');
        Firewall::isBeingAttacked('127.0.0.2');
        Firewall::isBeingAttacked('127.0.0.3');
        Firewall::isBeingAttacked('127.0.0.4');
        Firewall::isBeingAttacked('127.0.0.5');
        Firewall::isBeingAttacked('127.0.0.6');
        Firewall::isBeingAttacked('127.0.0.7');

        $this->assertFalse(Firewall::isBeingAttacked('127.0.0.8'));
    }

<<<<<<< Updated upstream
    public function testBlockerDisabled()
=======
    public function test_blocker_disabled()
>>>>>>> Stashed changes
    {
        $this->config('attack_blocker.enabled.ip', false);

        $this->config('attack_blocker.enabled.country', false);

        Firewall::isBeingAttacked('8.8.8.1');
        Firewall::isBeingAttacked('8.8.8.2');
        Firewall::isBeingAttacked('8.8.8.3');
        Firewall::isBeingAttacked('8.8.8.4');
        Firewall::isBeingAttacked('8.8.8.5');
        Firewall::isBeingAttacked('8.8.8.6');
        Firewall::isBeingAttacked('8.8.8.7');

        $this->assertFalse(Firewall::isBeingAttacked('8.8.8.8'));
    }

<<<<<<< Updated upstream
    public function testExpiration()
=======
    public function test_expiration()
>>>>>>> Stashed changes
    {
        $this->config('attack_blocker.allowed_frequency.ip.requests', 2);
        $this->config('attack_blocker.allowed_frequency.ip.seconds', 2);

        $this->assertFalse(Firewall::isBeingAttacked('172.17.0.1'));
        $this->assertFalse(Firewall::isBeingAttacked('172.17.0.1'));

        $this->assertNull(Firewall::responseToAttack());

        $this->assertTrue(Firewall::isBeingAttacked('172.17.0.1'));

        sleep(5);

        $this->assertFalse(Firewall::isBeingAttacked('172.17.0.1'));
    }
}
