<?php

namespace App\Tests\Validator;

use App\Entity\Document;
use App\Entity\Purchase;
use App\Validator\ApprovedPurchase;
use App\Validator\ApprovedPurchaseValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ApprovedPurchaseValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ApprovedPurchaseValidator
    {
        return new ApprovedPurchaseValidator();
    }

    public function testValidatePurchaseWithStatusNotPurchased(): void
    {
        $purchase = new Purchase();
        $purchase->setStatus('shipped');  // Statut autre que "purchased"

        // Crée une contrainte
        $constraint = new ApprovedPurchase();

        // Teste que la validation ne fait rien (return sans violation)
        $this->validator->validate($purchase, $constraint);

        // Aucune violation ne doit être ajoutée
        $this->assertNoViolation();
    }

    public function testValidatePurchaseWithDocumentsWaitingForSignature(): void
    {
        $document = new Document();
        $document->setStatus('waiting_for_signature');

        $purchase = new Purchase();
        $purchase->setStatus('purchased');
        $purchase->addDocument($document); // Ajoute le document à l'achat

        // Crée une contrainte
        $constraint = new ApprovedPurchase();

        // Teste que la validation ajoute une violation
        $this->validator->validate($purchase, $constraint);

        // Vérifie qu'une violation est bien ajoutée
        $this->buildViolation($constraint->message)
            ->atPath('property.path.status') // Le champ à violer
            ->assertRaised();
    }

    public function testValidatePurchaseWithoutDocuments(): void
    {
        $purchase = new Purchase();
        $purchase->setStatus('purchased');  // Statut "purchased" mais sans document

        // Crée une contrainte
        $constraint = new ApprovedPurchase();

        // Teste que la validation ne fait rien (aucune violation)
        $this->validator->validate($purchase, $constraint);

        // Aucune violation ne doit être ajoutée
        $this->assertNoViolation();
    }
}
