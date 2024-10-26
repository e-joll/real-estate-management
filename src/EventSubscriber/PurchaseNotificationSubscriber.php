<?php

namespace App\EventSubscriber;

use App\Entity\Notification;
use App\Event\PurchaseCompletedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PurchaseNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PurchaseCompletedEvent::class => 'onPurchaseCompleted',
        ];
    }

    public function onPurchaseCompleted(PurchaseCompletedEvent $event): void
    {
        $purchase = $event->getPurchase();

        $purchaseNotification = new Notification();
        $purchaseNotification->setRecipient($purchase->getBuyer());
        $purchaseNotification->setTitle('Achat finalisé');
        $purchaseNotification->setMessage("L'achat de {$purchase->getProperty()} est finalisé.");

        $this->entityManager->persist($purchaseNotification);
        $this->entityManager->flush();
    }
}
