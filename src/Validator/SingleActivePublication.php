<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class SingleActivePublication extends Constraint
{
    public string $message = 'Only one active service per artisan & service definition.';

    /**
     * Supporte l’attribut PHP 8 (#[]) et l’instanciation en test (new …()).
     *
     * @param array<string,mixed> $options
     */
    public function __construct(
        array $options = [],
        ?string $message = null,
        ?array $groups = null,
        $payload = null,
    ) {
        parent::__construct($options, $groups, $payload);
        if (null !== $message) {
            $this->message = $message;
        }
    }

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        return SingleActivePublicationValidator::class;
    }
}
