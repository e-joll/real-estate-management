<?php

namespace App\Controller\Admin\Director;

use App\Entity\Property;
use App\Repository\UserRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;


class PropertyCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly Security $security,
        private readonly UserRepository $userRepository
    )
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
                ->setDisabled()
                ->setPermission('ROLE_AGENT')
                ->setPermission('ROLE_DIRECTOR'),
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
                    'by_reference' => false, // Important for collection management
                ])
                ->onlyOnForms(),
            ArrayField::new('features', 'Caractéristiques')
                ->hideOnForm(),
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
        // TODO: Improve features filter
    }

    public function configureActions(Actions $actions): Actions
    {
        $user = $this->security->getUser();

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::NEW, new Expression('is_granted("ROLE_AGENT") or is_granted("ROLE_DIRECTOR")'))
            ->setPermission(Action::EDIT, new Expression('is_granted("ROLE_AGENT") or is_granted("ROLE_DIRECTOR")'))
            ->setPermission(Action::DELETE, new Expression('is_granted("ROLE_AGENT") or is_granted("ROLE_DIRECTOR")'))
            ->setPermission(Action::BATCH_DELETE, new Expression('is_granted("ROLE_AGENT") or is_granted("ROLE_DIRECTOR")'))
            // Edit action displayed only if the logged-in user is the agent
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) use ($user) {
                return $action->displayIf(static function (Property $entity) use ($user) {
                    return $entity->getAgent() === $user;
                });
            })
            ->update(Crud::PAGE_DETAIL, Action::EDIT, function (Action $action) use ($user) {
                return $action->displayIf(static function (Property $entity) use ($user) {
                    return $entity->getAgent() === $user;
                });
            })
            // Delete action displayed only if the logged-in user is the agent
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) use ($user) {
                return $action->displayIf(function (Property $entity) use ($user) {
                    return $entity->getAgent() === $user;
                });
            })
            ->update(Crud::PAGE_DETAIL, Action::DELETE, function (Action $action) use ($user) {
                return $action->displayIf(function (Property $entity) use ($user) {
                    return $entity->getAgent() === $user;
                });
            });
    }

    private function getUserAgentChoices(): array
    {
        $result = $this->userRepository->findByRole('AGENT');
        return array_reduce($result, function ($acc, $user) {
            $acc[$user->getFirstName()." ".$user->getLastName()] = $user;
            return $acc;
        }, []);
    }

    public function edit(AdminContext $context): KeyValueStore|RedirectResponse|Response
    {
        $user = $this->getUser();
        $property = $context->getEntity()->getInstance();

        if ($this->security->isGranted('ROLE_AGENT') && $property->getAgent() !== $user) {
            throw $this->createAccessDeniedException();
        }

        return parent::edit($context);
    }

    public function delete(AdminContext $context): KeyValueStore|RedirectResponse|Response
    {
        $user = $this->getUser();
        $property = $context->getEntity()->getInstance();

        if ($this->security->isGranted('ROLE_AGENT') && $property->getAgent() !== $user) {
            throw $this->createAccessDeniedException();
        }

        return parent::delete($context);
    }

    public function batchDelete(AdminContext $context, BatchActionDto $batchActionDto): Response
    {
        $user = $this->getUser();

        $entityManager = $this->container->get('doctrine')->getManagerForClass($batchActionDto->getEntityFqcn());
        $repository = $entityManager->getRepository($batchActionDto->getEntityFqcn());

        if ($this->security->isGranted('ROLE_AGENT')) {
            foreach ($batchActionDto->getEntityIds() as $entityId) {
                $entityInstance = $repository->find($entityId);
                if (!$entityInstance) {
                    continue;
                }
                if ($entityInstance->getAgent() !== $user) {
                    throw $this->createAccessDeniedException();
                }
            }
        }

        return parent::batchDelete($context, $batchActionDto);
    }
}
