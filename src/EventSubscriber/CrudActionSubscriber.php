<?php

namespace App\EventSubscriber;

use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityDeletedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CrudActionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private Security $security)
    {
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
        $session = $this->requestStack->getSession();
        $entityInstance = $event->getEntityInstance();

        $session->getFlashBag()->add('success', "La création de {$entityInstance} a été réalisée avec succès.");
    }

    public function afterEntityUpdated(AfterEntityUpdatedEvent $event): void
    {
        $session = $this->requestStack->getSession();
        $userLogIn = $this->security->getUser();
        $entityInstance = $event->getEntityInstance();

        if ($entityInstance == $userLogIn) {
            $session->getFlashBag()->add('success', "Votre profil a été modifié avec succès.");

        } else {
            $session->getFlashBag()->add('success', "La modification de {$entityInstance} a été effectuée avec succès.");

        }
    }

    public function afterEntityDeleted(AfterEntityDeletedEvent $event): void
    {
        $session = $this->requestStack->getSession();
        $entityInstance = $event->getEntityInstance();

        $session->getFlashBag()->add('success', "L'élément {$entityInstance} a été supprimé avec succès.");
    }

    public function afterCrudAction(AfterCrudActionEvent $event): void
    {
        $session = $this->requestStack->getSession();
        $responseParameters = $event->getResponseParameters();
        $pageName = $responseParameters->get('pageName');

        switch ($pageName) {
            case 'new':
                $isSubmitted = $event->getResponseParameters()->get('new_form')->isSubmitted();
                if ($isSubmitted) {
                    $session->getFlashBag()->add('danger', 'L\'entité n\'a pas été créée.');
                }
                break;
            case 'edit':
                $isSubmitted = $event->getResponseParameters()->get('edit_form')->isSubmitted();
                if ($isSubmitted) {
                    $session->getFlashBag()->add('danger', 'Les modifications n\'ont pas été prises en compte.');
                }
                break;
        }
    }
}
