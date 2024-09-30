<?php

namespace App\Controller\Admin\Director;

use App\Controller\Admin\Filter\UserRolesFilter;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('utilisateur')
            ->setEntityLabelInPlural('utilisateurs')
            ->setPageTitle(CRUD::PAGE_NEW, 'Créer un nouvel %entity_label_singular%')
            ->setPageTitle(CRUD::PAGE_DETAIL, fn (User $user) => sprintf('<b>%s</b>', $user))
            ->setPageTitle(CRUD::PAGE_EDIT, fn (User $user) => sprintf('Modifier <b>%s</b>', $user))
            ->setPageTitle(CRUD::PAGE_INDEX, 'Liste des %entity_label_plural%');
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->setDisabled(),
            TextField::new('email'),
            TextField::new('firstName', 'Prénom'),
            TextField::new('lastName', 'Nom'),
            ChoiceField::new('roles', 'Rôles')
                ->setChoices([
                    'Directeur' => 'ROLE_DIRECTOR',
                    'Agent' => 'ROLE_AGENT',
                    'Client' => 'ROLE_CUSTOMER'
                ])
                ->allowMultipleChoices(true)
                ->renderAsBadges([
                    'ROLE_DIRECTOR' => 'primary', // Couleur Bootstrap ou custom
                    'ROLE_AGENT' => 'success',
                    'ROLE_CUSTOMER' => 'danger',
                ]),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('firstName')
            ->add('lastName')
            ->add('email')
            ->add(UserRolesFilter::new('roles'));
    }

    public function configureActions(Actions $actions): Actions
    {
        $resetPassword = Action::new('resetPassword', 'Réinitialiser le mot de passe', 'fa fa-user-lock')
            ->linkToCrudAction('resetPassword');

        return $actions
            ->add(Crud::PAGE_INDEX, $resetPassword)
            ->add(Crud::PAGE_DETAIL, $resetPassword);
    }

    public function resetPassword(AdminContext $adminContext, EntityManagerInterface $entityManager , UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $adminContext->getEntity()->getInstance();

        $user->setPassword($passwordHasher->hashPassword($user, '123'));
        $entityManager->flush();

        return $this->redirect($this->container->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->generateUrl());
    }
}
