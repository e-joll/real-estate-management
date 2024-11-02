<?php

namespace App\Validator;

use App\Entity\Purchase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use UnexpectedValueException;

class ApprovedPurchaseValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$value instanceof Purchase) {
            throw new UnexpectedValueException($value, Purchase::class);
        }

        if (!$constraint instanceof ApprovedPurchase) {
            throw new UnexpectedValueException($constraint, ApprovedPurchase::class);
        }

        if ($value->getStatus() != 'purchased') {
            return;
        } else {
            foreach ($value->getDocuments() as $document) {
                if ($document->getStatus() == 'waiting_for_signature') {
                    $this->context->buildViolation($constraint->message)
                        ->atPath('status')
                        ->addViolation();
                }
            }
        }
    }
}
