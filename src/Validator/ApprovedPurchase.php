<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ApprovedPurchase extends Constraint
{
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public string $message = 'Les documents ne sont pas tous signés.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
