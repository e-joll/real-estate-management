<?php

namespace App\Scheduler\Handler;

use App\Entity\Notification;
use App\Repository\AppointmentRepository;
use App\Scheduler\Message\SendAppointmentNotifications;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendAppointmentNotificationsHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AppointmentRepository $appointmentRepository,
    )
    {
    }

    public function __invoke(SendAppointmentNotifications $message)
    {
        // TODO: Limit appointment queries to today's upcoming appointments only
        $now = new \DateTime('now');
        $appointments = $this->appointmentRepository->findAll();

        foreach ($appointments as $appointment) {
            $startDate = clone $appointment->getDate();
            if ($startDate->modify('-30 minutes') <= $now && $now < $appointment->getDate()) {
                $notification = new Notification();
                $notification->setTitle('Rappel '.$appointment);
                $notification->setMessage('RDV dans 30 minutes');
                $notification->setRecipient($appointment->getBuyer());

                $this->entityManager->persist($notification);
            }
        }
        $this->entityManager->flush();
    }
}