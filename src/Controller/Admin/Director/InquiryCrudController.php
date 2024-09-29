<?php

namespace App\Controller\Admin\Director;

use App\Entity\Inquiry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class InquiryCrudController extends AbstractCrudController
{
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

}
