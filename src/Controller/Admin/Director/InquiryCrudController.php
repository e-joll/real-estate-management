<?php

namespace App\Controller\Admin\Director;

use App\Entity\Inquiry;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class InquiryCrudController extends AbstractCrudController
{
    public function __construct(private Security $security)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Inquiry::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('demande')
            ->setEntityLabelInPlural('demandes')
            ->setPageTitle(CRUD::PAGE_NEW, 'Créer une nouvelle %entity_label_singular%')
            ->setPageTitle(CRUD::PAGE_DETAIL, fn (Inquiry $inquiry) => sprintf('<b>%s</b>', $inquiry))
            ->setPageTitle(CRUD::PAGE_EDIT, fn (Inquiry $inquiry) => sprintf('Modifier <b>%s</b>', $inquiry))
            ->setPageTitle(CRUD::PAGE_INDEX, 'Liste des %entity_label_plural%')
            ->setDefaultSort(['inquiredAt' => 'DESC']);
    }


    public function configureFields(string $pageName): iterable
    {
        $fields = [
            DateTimeField::new('inquiredAt', 'Demandé le')
                ->setFormTypeOption('view_timezone', $this->getUser()->getPreferredTimeZone()),
            AssociationField::new('property', 'Propriété'),
            TextField::new('subject', 'Sujet'),
            TextEditorField::new('message'),
        ];

        if (!$this->security->isGranted('ROLE_CUSTOMER')) {
            $fields[] = AssociationField::new('buyer', 'Acheteur');
        }

        return $fields;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('property');
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
        $inquiry = $context->getEntity()->getInstance();

        if ($this->security->isGranted('ROLE_CUSTOMER') && $inquiry->getBuyer() !== $user) {
            throw $this->createAccessDeniedException();
        } elseif ($this->security->isGranted('ROLE_AGENT') && $inquiry->getProperty()->getAgent() !== $user) {
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
