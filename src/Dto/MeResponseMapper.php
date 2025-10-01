<?php

namespace App\Dto;

use App\Entity\User;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class MeResponseMapper
{
    public function __construct(private ObjectMapperInterface $mapper)
    {
    }

    public function map(User $user): MeResponse
    {
        /** @var MeResponse $dto */
        $dto = $this->mapper->map($user, MeResponse::class);

        return $dto;
    }
}
