<?php

namespace App\Controller\Admin\Director;

use App\Entity\Purchase;
use App\Event\PurchaseCompletedEvent;
use App\Form\Type\DocumentType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

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
            IdField::new('id')
                ->setDisabled(),
            AssociationField::new('buyer')->renderAsEmbeddedForm(),
            AssociationField::new('property'),
            CollectionField::new('documents')
                ->setFormTypeOption('entry_type', DocumentType::class),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->overrideTemplate('crud/new', 'admin/crud/new.html.twig');
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
                if ($document->getStatus() != 'Signé') {
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
                if ($document->getStatus() != 'signe') {
                    $allDocumentsAreSigned = false;
                }
            }

            if ($allDocumentsAreSigned) {
                $this->eventDispatcher->dispatch(new PurchaseCompletedEvent($entityInstance));
            }
        }
    }
}
