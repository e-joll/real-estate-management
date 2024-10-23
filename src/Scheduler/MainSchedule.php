<?php

namespace App\Scheduler;

use App\EventListener\WorkerStoppedListener;
use App\Scheduler\Message\SendAppointmentNotifications;
use App\Scheduler\Message\UpdateAppointmentStatuses;
use phpDocumentor\Reflection\Types\This;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;
use Symfony\Component\Messenger\Worker;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\Event\FailureEvent;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Throwable;

#[AsSchedule('default')]
final class MainSchedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
        private EventDispatcherInterface $dispatcher,
        private LoggerInterface $logger
    ) {
    }

    public function __toString(): string
    {
        return 'MainSchedule';
    }

    public function getSchedule(): Schedule
    {
        return (new Schedule($this->dispatcher))
            ->add(
                RecurringMessage::every('5 seconds', new UpdateAppointmentStatuses()),
                RecurringMessage::every('5 seconds', new SendAppointmentNotifications()),
            )
            ->after(function () {
                $this->logger->info('AFTER');
            })
            ->onFailure(
                function (FailureEvent $event) {
                    $this->logger->error('Erreur lors de l\'exécution d\'une tâche : ' . $event->getSchedule());
                }
            )
//            ->stateful($this->cache)
        ;
    }
}
