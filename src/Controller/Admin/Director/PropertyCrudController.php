<?php

namespace App\Controller\Admin\Director;

use App\Entity\Property;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PropertyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Property::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('propriété')
            ->setEntityLabelInPlural('propriétés')
            ->setPageTitle(CRUD::PAGE_NEW, 'Créer une nouvelle %entity_label_singular%')
            ->setPageTitle(CRUD::PAGE_DETAIL, fn (Property $property) => sprintf('<b>%s</b>', $property))
            ->setPageTitle(CRUD::PAGE_EDIT, fn (Property $property) => sprintf('Modifier <b>%s</b>', $property))
            ->setPageTitle(CRUD::PAGE_INDEX, 'Liste des %entity_label_plural%');
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->onlyOnForms()
                ->setDisabled(),
            TextField::new('title', 'Titre'),
            TextEditorField::new('description')
                ->setTemplatePath('admin/content_text_editor_field.html.twig'),
            MoneyField::new('price', 'Prix')
                ->setCurrency('EUR'),
            TextField::new('location', 'Lieu'),
            AssociationField::new('agent')
                ->setQueryBuilder(
                    fn (QueryBuilder $queryBuilder) => $queryBuilder
                        ->andWhere('entity.roles LIKE :val')
                        ->setParameter('val', '%AGENT%')
                ),
            AssociationField::new('features', 'Caractéristiques')
                ->setFormTypeOptions([
                    'by_reference' => false, // Important pour la gestion des collections
                ])
                ->hideOnIndex(),
            ArrayField::new('features', 'Caractéristiques')
                ->onlyOnIndex(),
        ];
    }
}
