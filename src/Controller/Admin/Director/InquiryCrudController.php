<?php

namespace App\Controller\Admin\Director;

use App\Entity\Inquiry;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Bundle\SecurityBundle\Security;

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
            ->setPageTitle(CRUD::PAGE_INDEX, 'Liste des %entity_label_plural%');
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('property', 'Propriété'),
            TextField::new('subject', 'Sujet'),
            TextEditorField::new('message'),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('property');
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $user = $this->security->getUser();
        $entityInstance->setBuyer($user);

        parent::persistEntity($entityManager, $entityInstance);
    }

}
