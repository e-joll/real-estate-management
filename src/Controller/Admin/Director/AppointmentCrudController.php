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
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

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
            ->setPageTitle(CRUD::PAGE_INDEX, 'Liste des %entity_label_plural%')
            ->setDefaultSort(['date' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            AssociationField::new('property', 'Propriété'),
            // TODO: Trouver une meilleure solution pour éviter l'erreur dans le cas où la date n'est pas renseignée
            DateTimeField::new('date', 'Date')
                ->setEmptyData('cheat to avoid Error')
                /*->setFormTypeOption('view_timezone', $this->getUser()->getPreferredTimeZone())*/,
            TimeField::new('duration','Durée')
                ->renderAsChoice()
                ->setFormTypeOptions([
                    'hours' => range(0,2),
                    'minutes' => [0,30]
                ]),
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
        ];

        if (!$this->security->isGranted('ROLE_CUSTOMER')) {
            $fields[] = AssociationField::new('buyer', 'Acheteur');
        }

        return $fields;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('property')
            ->add(DateTimeFilter::new('date')
                ->setFormTypeOption('value_type', DateType::class)
            );
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::EDIT)
            ->setPermission(Action::NEW, 'ROLE_CUSTOMER')
            ->setPermission(Action::DELETE, 'ROLE_CUSTOMER')
            ->setPermission(Action::BATCH_DELETE, 'ROLE_CUSTOMER');
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

        $user = $this->getUser();

        if ($this->isGranted('ROLE_CUSTOMER')) {
            $queryBuilder->andWhere('entity.buyer = :user')
                ->setParameter('user', $user);
        } elseif ($this->isGranted('ROLE_AGENT')) {
            $queryBuilder->join('entity.property', 'p')
                ->andWhere('p.agent = :user')
                ->setParameter('user', $user);
        }

        return $queryBuilder;
    }

    public function detail(AdminContext $context): KeyValueStore|Response
    {
        $user = $this->getUser();
        $appointment = $context->getEntity()->getInstance();

        if ($this->security->isGranted('ROLE_CUSTOMER') && $appointment->getBuyer() !== $user) {
            throw $this->createAccessDeniedException();
        } elseif ($this->security->isGranted('ROLE_AGENT') && $appointment->getProperty()->getAgent() !== $user) {
            throw $this->createAccessDeniedException();
        }

        return parent::detail($context);
    }

    public function delete(AdminContext $context): KeyValueStore|RedirectResponse|Response
    {
        $user = $this->getUser();
        $inquiry = $context->getEntity()->getInstance();

        if ($inquiry->getBuyer() !== $user) {
            throw $this->createAccessDeniedException();
        }

        return parent::delete($context);
    }
}
