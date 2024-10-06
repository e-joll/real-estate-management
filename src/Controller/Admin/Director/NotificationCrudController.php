<?php

namespace App\Controller\Admin\Director;

use App\Entity\Notification;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use phpDocumentor\Reflection\Types\Boolean;

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

    public function configureFields(string $pageName): iterable
    {
        return [
            /*IdField::new('id'),*/
            TextField::new('title', 'Sujet'),
            TextEditorField::new('message'),
            BooleanField::new('isRead', 'Lue'),
            DateTimeField::new('createdAt', 'Créée le'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_INDEX, Action::EDIT);
    }
}
