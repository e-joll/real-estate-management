<?php

namespace App\Controller\Admin\Director;

use App\Entity\Appointment;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use Symfony\Bundle\SecurityBundle\Security;

class AppointmentCrudController extends AbstractCrudController
{
    public function __construct(private Security $security)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Appointment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('rendez-vous')
            ->setEntityLabelInPlural('rendez-vous')
            ->setPageTitle(CRUD::PAGE_NEW, 'Créer un nouveau %entity_label_singular%')
            ->setPageTitle(CRUD::PAGE_DETAIL, fn (Appointment $appointment) => sprintf('<b>%s</b>', $appointment))
            ->setPageTitle(CRUD::PAGE_EDIT, fn (Appointment $appointment) => sprintf('Modifier <b>%s</b>', $appointment))
            ->setPageTitle(CRUD::PAGE_INDEX, 'Liste des %entity_label_plural%');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('property', 'Propriété'),
            DateTimeField::new('date', 'Date')
                ->setEmptyData('cheat to avoid Error')
                ->setFormTypeOption('view_timezone', $this->getUser()->getPreferredTimeZone()),
            // TODO: Trouver une meilleure solution pour éviter l'erreur dans le cas où la date n'est pas renseignée
            TimeField::new('duration','Durée'),
            TextField::new('status', 'Statut')
                ->onlyOnDetail(),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('property')
            ->add('date');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $user = $this->security->getUser();

        // Set the buyer to the currently logged-in user
        $entityInstance->setBuyer($user);

        // Convert the user-submitted date to UTC based on the user's preferred time zone
        $date = $entityInstance->getDate()->format('Y-m-d H:i:s');
        $preferredTimeZone = new \DateTimeZone($user->getPreferredTimeZone());
        $datePreferredTimeZone = new \DateTime($date, $preferredTimeZone);
        $dateUTC = $datePreferredTimeZone->setTimezone(new \DateTimeZone('UTC'));
        $entityInstance->setDate($dateUTC);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $user = $this->security->getUser();

        // Convert the user-submitted date to UTC based on the user's preferred time zone
        $date = $entityInstance->getDate()->format('Y-m-d H:i:s');
        $preferredTimeZone = new \DateTimeZone($user->getPreferredTimeZone());
        $datePreferredTimeZone = new \DateTime($date, $preferredTimeZone);
        $dateUTC = $datePreferredTimeZone->setTimezone(new \DateTimeZone('UTC'));
        $entityInstance->setDate($dateUTC);

        parent::updateEntity($entityManager, $entityInstance);
    }
}
