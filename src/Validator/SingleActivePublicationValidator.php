<?php

namespace App\Validator;

use App\Entity\ArtisanService;
use App\Enum\ArtisanServiceStatus;
use App\Repository\ArtisanServiceRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SingleActivePublicationValidator extends ConstraintValidator
{
    public function __construct(private readonly ArtisanServiceRepository $repository)
    {
    }

    /**
     * @param ArtisanService|null $value
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof SingleActivePublication) {
            return; // mauvaise contrainte (ne devrait pas arriver)
        }
        if (!$value instanceof ArtisanService) {
            return; // not our target
        }

        // We only care when status is ACTIVE
        if (ArtisanServiceStatus::ACTIVE !== $value->getStatus()) {
            return;
        }

        // If required associations are missing, skip (another validator will catch nulls)
        $artisan = $value->getArtisanProfile();
        $definition = $value->getServiceDefinition();
        if (null === $artisan || null === $definition) {
            return;
        }

        $other = $this->repository->findOneActiveByArtisanAndDefinition(
            $artisan,
            $definition,
            $value->getId()
        );

        if (null !== $other) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('status')
                ->addViolation();
        }
    }
}
