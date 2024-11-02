<?php

namespace App\EventSubscriber;

use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityDeletedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CrudActionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        RequestStack $requestStack,
        private ?SessionInterface $session)
    {
        $this->session = $requestStack->getSession();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AfterEntityPersistedEvent::class => 'afterEntityPersisted',
            AfterEntityUpdatedEvent::class => 'afterEntityUpdated',
            AfterEntityDeletedEvent::class => 'afterEntityDeleted',
            AfterCrudActionEvent::class => 'afterCrudAction',
        ];
    }

    public function afterEntityPersisted(AfterEntityPersistedEvent $event): void
    {
        $entityInstance = $event->getEntityInstance();
        $this->session->getFlashBag()->add('success', "Création de {$entityInstance} avec succès.");
    }

    public function afterEntityUpdated(AfterEntityUpdatedEvent $event): void
    {
        $entityInstance = $event->getEntityInstance();
        $this->session->getFlashBag()->add('success', "Modification de {$entityInstance} avec succès.");
    }

    public function afterEntityDeleted(AfterEntityDeletedEvent $event): void
    {
        $entityInstance = $event->getEntityInstance();
        $this->session->getFlashBag()->add('success', "Suppression de {$entityInstance} avec succès.");
    }

    public function afterCrudAction(AfterCrudActionEvent $event): void
    {
        $responseParameters = $event->getResponseParameters();
        $pageName = $responseParameters->get('pageName');

        switch ($pageName) {
            case 'new':
                $isSubmitted = $event->getResponseParameters()->get('new_form')->isSubmitted();
                if ($isSubmitted) {
                    $this->session->getFlashBag()->add('danger', 'L\'entité n\'a pas été créée.');
                }
                break;
            case 'edit':
                $isSubmitted = $event->getResponseParameters()->get('edit_form')->isSubmitted();
                if ($isSubmitted) {
                    $this->session->getFlashBag()->add('danger', 'Les modifications n\'ont pas été prises en compte.');
                }
                break;
        }
    }
}
