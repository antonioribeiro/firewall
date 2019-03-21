<?php

namespace PragmaRX\Firewall\Tests;

use Illuminate\Http\Response;
use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;

class AttackBlockerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->config('notifications.enabled', true);

        $this->config('attack_blocker.allowed_frequency.ip.requests', 2);

        $this->config('attack_blocker.enabled.ip', true);

        $this->config('attack_blocker.enabled.country', true);

        $this->config('attack_blocker.allowed_frequency.country.requests', 3);

        config()->set('services.slack.webhook_url', '12345');
    }

    public function test_attack()
    {
        $this->config('notifications.enabled', false);

        $this->config('attack_blocker.allowed_frequency.ip.requests', 2);

        $this->assertFalse(Firewall::isBeingAttacked('172.17.0.1'));
        $this->assertFalse(Firewall::isBeingAttacked('172.17.0.1'));

        $this->assertNull(Firewall::responseToAttack());

        $this->assertTrue(Firewall::isBeingAttacked('172.17.0.1'));

        $this->assertInstanceOf(Response::class, Firewall::responseToAttack());
    }

    public function test_send_notification_ip_attack()
    {
        $this->assertFalse(Firewall::isBeingAttacked('8.8.8.8'));
        $this->assertFalse(Firewall::isBeingAttacked('8.8.8.8'));
        $this->assertTrue(Firewall::isBeingAttacked('8.8.8.8'));
    }

    public function test_send_notification_country_attack()
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

    public function test_send_notification_country_block_attack_and_blacklist()
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

    public function test_send_notification_no_country_ip_attack()
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

    public function test_blocker_disabled()
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

    public function test_expiration()
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
