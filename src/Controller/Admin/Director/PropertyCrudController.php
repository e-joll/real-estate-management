<?php

namespace App\Controller\Admin\Director;

use App\Entity\Property;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;


class PropertyCrudController extends AbstractCrudController
{
    public function __construct(private UserRepository $userRepository)
    {

    }

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

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('title')
            ->add('description')
            ->add('location')
            ->add(ChoiceFilter::new('agent')
                ->setChoices($this->getUserAgentChoices()))
            ->add('price')
            ->add('features');
    }

    private function getUserAgentChoices(): array
    {
        $result = $this->userRepository->findByRole('AGENT');
        return array_reduce($result, function ($acc, $user) {
            $acc[$user->getFirstName()." ".$user->getLastName()] = $user;
            return $acc;
        }, []);
    }
}
