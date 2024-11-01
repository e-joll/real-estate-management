<?php

namespace App\Controller\Admin\Director;

use App\Entity\Document;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class DocumentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Document::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('document')
            ->setEntityLabelInPlural('documents')
            ->setPageTitle(CRUD::PAGE_NEW, 'Créer un nouveau %entity_label_singular%')
            ->setPageTitle(CRUD::PAGE_DETAIL, fn (Document $document) => sprintf('<b>%s</b>', $document))
            ->setPageTitle(CRUD::PAGE_EDIT, fn (Document $document) => sprintf('Modifier <b>%s</b>', $document))
            ->setPageTitle(CRUD::PAGE_INDEX, 'Liste des %entity_label_plural%')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('purchase'),
            TextField::new('name', 'Nom'),
            ChoiceField::new('status', 'Statut')
                ->setChoices([
                    'En attente de signature' => 'en_attente',
                    'Signé' => 'signe',
                ])
                ->renderAsBadges([
                    'En attente de signature' => 'info',
                    'Signé' => 'success',
                ])
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::EDIT);
    }

}
