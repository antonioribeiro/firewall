<?php

namespace PragmaRX\Firewall\Listeners;

use Notification as IlluminateNotification;
use PragmaRX\Firewall\Events\AttackDetected;
use PragmaRX\Firewall\Notifications\Notification;

class NotifyAdmins
{
    /**
     * @return static
     */
    private function getNotifiableUsers()
    {
        return collect(config('firewall.notifications.users.emails'))->map(function ($item) {
            if (class_exists($class = config('firewall.notifications.users.model'))) {
                $model = app($class);

                $model->email = $item;

                return $model;
            }
        })->filter();
    }

    /**
     * Handle the event.
     *
     * @param AttackDetected $event
     *
     * @return void
     */
    public function handle(AttackDetected $event)
    {
        try {
            IlluminateNotification::send(
                $this->getNotifiableUsers(),
                new Notification($event->record, $event->channel)
            );
        } catch (\Exception $exception) {
            info($exception);
        } catch (\ErrorException $exception) {
            info($exception);
        }
    }
}
