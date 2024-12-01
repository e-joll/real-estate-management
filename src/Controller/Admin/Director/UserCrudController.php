<?php

namespace App\Controller\Admin\Director;

use App\Controller\Admin\Filter\UserRolesFilter;
use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Exception\InsufficientEntityPermissionException;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimezoneField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $request,
        private readonly UserPasswordHasherInterface $passwordHasher
    )
    {
    }

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
            ->setPageTitle(CRUD::PAGE_INDEX, 'Liste des %entity_label_plural%')
            ->overrideTemplate('crud/edit', 'admin/crud/edit_user.html.twig');
    }


    public function configureFields(string $pageName): iterable
    {
        $fields = [
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
                    'ROLE_DIRECTOR' => 'primary',
                    'ROLE_AGENT' => 'success',
                    'ROLE_CUSTOMER' => 'danger',
                ]),
            TimezoneField::new('preferredTimeZone', 'Fuseau horaire')
                ->setValue('Europe/Paris'),
            BooleanField::new('isApproved', 'Approuvé')
                ->setPermission('ROLE_DIRECTOR'),
            TextField::new('plainPassword', '')
                ->setFormType(ChangePasswordFormType::class)
                ->setFormTypeOption('mapped', false)
                ->setFormTypeOption('validation_groups', 'edit')
        ];

        if (str_contains($this->request->getCurrentRequest()->query->get('crudControllerFqcn'), "UserCrudController")) {
            $fieldSets = [
                FormField::addFieldset('Aide pour remplir le formulaire')
                    ->setHelp('<ol><li>1ere étape</li><li>2eme étape</li></ol>'),
                FormField::addFieldset('Données'),
            ];

            return array_merge($fieldSets, $fields);
        }

        return $fields;
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
        $userLoggedIn = $this->security->getUser();

        $RESET_PASSWORD_ACTION = 'resetPassword';
        $resetPassword = Action::new($RESET_PASSWORD_ACTION, 'Réinitialiser le mot de passe', 'fa fa-user-lock')
            ->linkToCrudAction($RESET_PASSWORD_ACTION)
            ->setHtmlAttributes([
                'data-bs-toggle' => 'modal',
                'data-bs-target' => '#modal-reset-password'
            ]);

        return $actions
//            ->add(Crud::PAGE_INDEX, $resetPassword)
//            ->add(Crud::PAGE_DETAIL, $resetPassword)
            ->add(Crud::PAGE_EDIT, $resetPassword)
            ->add(Crud::PAGE_EDIT, Action::DELETE)
            ->disable(Action::BATCH_DELETE)
            ->setPermission($RESET_PASSWORD_ACTION, 'ROLE_DIRECTOR')
            ->setPermission(Action::INDEX, new Expression('is_granted("ROLE_AGENT") or is_granted("ROLE_DIRECTOR")'))
            ->setPermission(Action::NEW, new Expression('is_granted("ROLE_AGENT") or is_granted("ROLE_DIRECTOR")'))
            ->setPermission(Action::EDIT, new Expression('is_granted("ROLE_AGENT") or is_granted("ROLE_DIRECTOR")'))
            ->setPermission(Action::DELETE, new Expression('is_granted("ROLE_AGENT") or is_granted("ROLE_DIRECTOR")'))
            // Edit action displayed only if the logged-in user can edit the selected user
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) use ($userLoggedIn) {
                return $action->displayIf(function (User $entity) use ($userLoggedIn) {
                    $roles = $entity->getRoles();
                    return ($this->isGranted('ROLE_AGENT') && (in_array('ROLE_CUSTOMER', $roles) || $entity === $userLoggedIn))
                        || $this->isGranted('ROLE_DIRECTOR');
                });
            })
            ->update(Crud::PAGE_DETAIL, Action::EDIT, function (Action $action) use ($userLoggedIn) {
                return $action->displayIf(function (User $entity) use ($userLoggedIn) {
                    $roles = $entity->getRoles();
                    return ($this->isGranted('ROLE_AGENT') && (in_array('ROLE_CUSTOMER', $roles) || $entity === $userLoggedIn))
                        || $this->isGranted('ROLE_DIRECTOR');
                });
            })
            // Delete action displayed only if the logged-in user can delete the selected user
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) use ($userLoggedIn) {
                return $action->displayIf(function (User $entity) use ($userLoggedIn) {
                    $roles = $entity->getRoles();
                    return ($this->isGranted('ROLE_AGENT') && (in_array('ROLE_CUSTOMER', $roles) || $entity === $userLoggedIn))
                        || $this->isGranted('ROLE_DIRECTOR');
                });
            })
            ->update(Crud::PAGE_DETAIL, Action::DELETE, function (Action $action) use ($userLoggedIn) {
                return $action->displayIf(function (User $entity) use ($userLoggedIn) {
                    $roles = $entity->getRoles();
                    return ($this->isGranted('ROLE_AGENT') && (in_array('ROLE_CUSTOMER', $roles) || $entity === $userLoggedIn))
                        || $this->isGranted('ROLE_DIRECTOR');
                });
            });
    }

    public function resetPassword(AdminContext $adminContext, EntityManagerInterface $entityManager): Response
    {
        $user = $adminContext->getEntity()->getInstance();

        $user->setPassword($this->passwordHasher->hashPassword($user, '123'));
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->redirect($this->container->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->generateUrl());
    }

    public function detail(AdminContext $context): KeyValueStore|Response
    {
        $entityInstance = $context->getEntity()->getInstance();

        $roles = $entityInstance->getRoles();

        if ($this->security->isGranted('ROLE_CUSTOMER') && !in_array('ROLE_AGENT', $roles)) {
            throw new InsufficientEntityPermissionException($context);
        }

        return parent::detail($context);
    }

    public function edit(AdminContext $context): KeyValueStore|RedirectResponse|Response
    {
        $entityInstance = $context->getEntity()->getInstance();
        $roles = $entityInstance->getRoles();

        if ($this->security->isGranted('ROLE_AGENT') && in_array('ROLE_AGENT', $roles)) {
            throw new ForbiddenActionException($context);
        } elseif ($userData = $context->getRequest()->get('User')) {
            // If plainPassword is provided, update the password
            $plainPassword = $userData['plainPassword']['plainPassword']['first'];
            if (!empty($plainPassword)) {
                $entityInstance->setPassword($this->passwordHasher->hashPassword($entityInstance, $plainPassword));
            }
        }

        return parent::edit($context);
    }

    public function delete(AdminContext $context): KeyValueStore|RedirectResponse|Response
    {
        $entityInstance = $context->getEntity()->getInstance();
        $roles = $entityInstance->getRoles();

        if ($this->security->isGranted('ROLE_AGENT') && in_array('ROLE_AGENT', $roles)) {
            throw $this->createAccessDeniedException();
        }

        return parent::delete($context);
    }
}
