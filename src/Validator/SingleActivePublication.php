<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Apply on the ArtisanService entity class.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class SingleActivePublication extends Constraint
{
    public string $message = 'Only one ACTIVE offer is allowed per artisan and service definition.';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
