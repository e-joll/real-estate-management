<?php

namespace App\Controller\Admin\Director;

use App\Entity\Purchase;
use App\Event\PurchaseCompletedEvent;
use App\Form\Type\DocumentType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function Symfony\Component\Translation\t;

class PurchaseCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserRepository  $userRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    )
    {
    }

    public static function getEntityFqcn(): string
    {
        return Purchase::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('buyer', 'Acheteur')
                ->renderAsEmbeddedForm(),
            AssociationField::new('property', 'Propriété'),
            CollectionField::new('documents')
                ->setFormTypeOption('entry_type', DocumentType::class),
            ChoiceField::new('status', 'Statut')
                ->setChoices([
                    'En cours' => 'in_progress',
                    'Acheté' => 'purchased',
                ])
                ->renderAsBadges([
                    'in_progress' => 'info',
                    'purchased' => 'success',
                ])
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->overrideTemplate('crud/new', 'admin/crud/new.html.twig')
            ->overrideTemplate('crud/edit', 'admin/crud/edit_purchase.html.twig');
    }

    public function configureActions(Actions $actions): Actions
    {
        $SAVE_AND_CONFIRM = 'saveAndConfirm';
        $saveAndConfirm = Action::new($SAVE_AND_CONFIRM, t('action.save_and_continue', domain: 'EasyAdminBundle'))
            ->linkToCrudAction(Action::EDIT)
            ->setHtmlAttributes([
                'data-bs-toggle' => 'modal',
                'data-bs-target' => '#modal-save-and-confirm',
            ])
            ->addCssClass('btn btn-primary')->displayAsButton();
        return $actions
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN)
            ->add(Crud::PAGE_EDIT, $saveAndConfirm);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $buyer = $this->userRepository->findOneBy(['email' => $entityInstance->getBuyer()->getEmail()]);

        $entityInstance->setBuyer($buyer);

        parent::persistEntity($entityManager, $entityInstance);

        $documents = $entityInstance->getDocuments();
        if (!empty($documents)) {
            $allDocumentsAreSigned = true;
            foreach ($documents as $document) {
                if ($document->getStatus() != 'signed') {
                    $allDocumentsAreSigned = false;
                }
            }

            if ($allDocumentsAreSigned) {
                $this->eventDispatcher->dispatch(new PurchaseCompletedEvent($entityInstance));
            }
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::updateEntity($entityManager, $entityInstance);

        $documents = $entityInstance->getDocuments();
        if (!empty($documents)) {
            $allDocumentsAreSigned = true;
            foreach ($documents as $document) {
                if ($document->getStatus() != 'signed') {
                    $allDocumentsAreSigned = false;
                }
            }

            if ($allDocumentsAreSigned) {
                $this->eventDispatcher->dispatch(new PurchaseCompletedEvent($entityInstance));
            }
        }
    }
}
