<?php

namespace App\Scheduler;

use App\Scheduler\Message\SendAppointmentNotifications;
use App\Scheduler\Message\UpdateAppointmentStatuses;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule('default')]
final class MainSchedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(
                RecurringMessage::every('5 seconds', new UpdateAppointmentStatuses()),
                RecurringMessage::every('5 seconds', new SendAppointmentNotifications()),
            )
            ->stateful($this->cache)
        ;
    }
}
