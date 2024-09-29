<?php

namespace App\Controller\Admin\Director;

use App\Entity\Appointment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

class AppointmentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Appointment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('rendez-vous')
            ->setEntityLabelInPlural('rendez-vous')
            ->setPageTitle(CRUD::PAGE_NEW, 'Créer une nouvelle %entity_label_singular%')
            ->setPageTitle(CRUD::PAGE_DETAIL, fn (Appointment $appointment) => sprintf('<b>%s</b>', $appointment))
            ->setPageTitle(CRUD::PAGE_EDIT, fn (Appointment $appointment) => sprintf('Modifier <b>%s</b>', $appointment))
            ->setPageTitle(CRUD::PAGE_INDEX, 'Liste des %entity_label_plural%');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('property', 'Propriété'),
            DateTimeField::new('date', 'Date'),
        ];
    }
}
