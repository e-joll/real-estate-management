<?php

namespace App\Scheduler\Handler;

use App\Repository\AppointmentRepository;
use App\Scheduler\Message\UpdateAppointmentStatuses;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateAppointmentStatusesHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AppointmentRepository $appointmentRepository
    )
    {
    }

    public function __invoke(UpdateAppointmentStatuses $message)
    {
        // TODO: Limit appointment queries to today's upcoming appointments only
        $now = new \DateTime('now');
//        $now->setTimeZone(new \DateTimeZone('Europe/Paris'));
        $appointments = $this->appointmentRepository->findAll();


        // Ouvrir le fichier en mode ajout
        $filePath = 'C:\Users\jobif\Desktop\log.txt'; // Spécifiez le chemin de votre fichier
        $file = fopen($filePath, 'a'); // 'a' pour ajouter à la fin du fichier


        foreach ($appointments as $appointment) {
            $duration = new \DateInterval('PT' . $appointment->getDuration()->format('H') . 'H' .
                $appointment->getDuration()->format('i') . 'M');
            $startAppointment = clone $appointment->getDate()/*->setTimeZone(new \DateTimeZone('Europe/Paris'))*/;
            $endAppointment = clone $startAppointment;
            $endAppointment->add($duration);

            if ($startAppointment <= $now && $now < $endAppointment) {
                $status = 'En cours';
            } elseif ($endAppointment < $now) {
                $status ='Passé';
            } else {
                $status = 'A venir';
            }

            // Écrire les informations dans le fichier
            $line = sprintf(
                "Appointment ID: %d, Start: %s, End: %s, Duration: %s, Now: %s, Status: %s\n",
                $appointment->getId(),
                $startAppointment->format('Y-m-d H:i:s P'),
                $endAppointment->format('Y-m-d H:i:s P'),
                $duration->format('%h heures %i minutes'),
                $now->format('Y-m-d H:i:s P'),
                $status,
            );

            fwrite($file, $line); // Écrire la ligne dans le fichier

            if ($appointment->getDate() < $now && $now < $endAppointment) {
                $appointment->setStatus('En cours');
            } elseif ($endAppointment <= $now) {
                $appointment->setStatus('Passé');
            }
        }
//            if ($appointment->getDate()->diff($now)) {
//
//            }
        // Fermer le fichier après l'écriture
        fclose($file);

        $this->entityManager->flush();
    }
}