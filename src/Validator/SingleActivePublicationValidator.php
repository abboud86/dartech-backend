<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SingleActivePublicationValidator extends ConstraintValidator
{
    /**
     * @param object|null $value The entity being validated
     */
    public function validate($value, Constraint $constraint): void
    {
        // Logic implemented at step C3
    }
}
