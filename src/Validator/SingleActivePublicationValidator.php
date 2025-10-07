<?php

namespace App\Validator;

use App\Entity\ArtisanService;
use App\Enum\ArtisanServiceStatus;
use App\Repository\ArtisanServiceRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class SingleActivePublicationValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ArtisanServiceRepository $repository,
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        // On ne traite que nos cas
        if (!$value instanceof ArtisanService) {
            return;
        }
        if (!$constraint instanceof SingleActivePublication) {
            // Sécurité pour PHPStan & cas improbables
            return;
        }

        // Règle: on ne vérifie que pour ACTIVE
        if (ArtisanServiceStatus::ACTIVE !== $value->getStatus()) {
            return;
        }

        $exists = $this->repository->hasActiveForCouple(
            $value->getArtisanProfile(),
            $value->getServiceDefinition(),
            $value->getId() // null si création
        );

        if ($exists) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('status') // attendu par le test
                ->addViolation();
        }
    }
}
