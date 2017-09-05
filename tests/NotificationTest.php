<?php

namespace PragmaRX\Firewall\Tests;

use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;

class NotificationTest extends TestCase
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
    }
}
