<?php

namespace App\Controller\Admin\Director;

use App\Entity\Notification;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class NotificationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Notification::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('notification')
            ->setEntityLabelInPlural('notifications')
            ->setPageTitle(CRUD::PAGE_NEW, 'Créer une nouvelle %entity_label_singular%')
            ->setPageTitle(CRUD::PAGE_DETAIL, fn (Notification $notification) => sprintf('<b>%s</b>', $notification))
            ->setPageTitle(CRUD::PAGE_EDIT, fn (Notification $notification) => sprintf('Modifier <b>%s</b>', $notification))
            ->setPageTitle(CRUD::PAGE_INDEX, 'Liste des %entity_label_plural%');
    }
    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
