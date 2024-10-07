<?php

namespace App\Controller\Admin\Director;

use App\Entity\Appointment;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
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
            // TODO: Trouver une meilleure solution pour éviter l'erreur dans le cas où la date n'est pas renseignée
            DateTimeField::new('date', 'Date')
                ->setEmptyData('cheat to avoid Error')
                /*->setFormTypeOption('view_timezone', $this->getUser()->getPreferredTimeZone())*/,
            TimeField::new('duration','Durée'),
            ChoiceField::new('status', 'Statut')
                ->setChoices([
                    'A venir' => 'A venir',
                    'En cours' => 'En cours',
                    'Passé' => 'Passé',
                ])
                ->renderAsBadges([
                    'A venir' => 'info',
                    'En cours' => 'success',
                    'Passé' => 'danger',
                ])
                ->hideOnForm(),
            AssociationField::new('buyer', 'Acheteur')
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

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        if ($this->isGranted('ROLE_CLIENT') or $this->isGranted('ROLE_CUSTOMER')) {
            $user = $this->getUser();
            $queryBuilder->andWhere('entity.buyer = :user')
                ->setParameter('user', $user);
        }

        return $queryBuilder;
    }
}
