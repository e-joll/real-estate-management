<?php

namespace App\Controller\Admin\Director;

use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class NotificationCrudController extends AbstractCrudController
{
    public function __construct(private Security $security)
    {
    }

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
            ->setPageTitle(CRUD::PAGE_INDEX, 'Liste des %entity_label_plural%')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            /*IdField::new('id'),*/
            TextField::new('title', 'Sujet')
                ->setDisabled(),
            TextEditorField::new('message')
                ->setDisabled(),
            BooleanField::new('isRead', 'Lue'),
            DateTimeField::new('createdAt', 'Créée le')
                ->setDisabled(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->addBatchAction(Action::new('markAsRead', 'Marquer comme lu')
                ->linkToCrudAction('markAsRead')
                ->displayAsButton()
                ->setCssClass('btn btn-success'))
            ->disable(Action::NEW)
            ->disable(Action::DELETE)
            ->disable(Action::BATCH_DELETE)
            ->setPermission(Action::EDIT, 'ROLE_CUSTOMER')
            ->setPermission('markAsRead', 'ROLE_CUSTOMER');
    }


    public function markAsRead(BatchActionDto $batchActionDto, EntityManagerInterface $entityManager): RedirectResponse
    {
        // Retrieving selected entities in the mass action
        $selectedNotifications = $batchActionDto->getEntityIds();

        if ($selectedNotifications) {
            $notifications = $entityManager->getRepository(Notification::class)
                ->findBy(['id' => $selectedNotifications]);

            foreach ($notifications as $notification) {
                $notification->setIsRead(true);
            }

            $entityManager->flush();
        }

        // Redirect to the index page
        return $this->redirect($batchActionDto->getReferrerUrl());
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $user = $this->getUser();

        if ($this->isGranted('ROLE_CUSTOMER')) {
            $queryBuilder->andWhere('entity.recipient = :user')
                ->setParameter('user', $user);
        }

        return $queryBuilder;
    }

    public function edit(AdminContext $context): KeyValueStore|RedirectResponse|Response
    {
        $user = $this->getUser();
        $notification = $context->getEntity()->getInstance();

        if ($this->security->isGranted('ROLE_CUSTOMER') && $notification->getRecipient() !== $user) {
            throw $this->createAccessDeniedException();
        }

        return parent::edit($context);
    }
}
