<?php

namespace App\Dto;

use Symfony\Component\ObjectMapper\Attribute\Map;

final class MeResponse
{
    // id ULID (source: User::id) -> string
    #[Map(source: 'id', transform: 'strval')]
    public string $id = '';

    // email direct (source: User::email)
    #[Map(source: 'email')]
    public string $email = '';

    // ArtisanProfile.* (mapping depuis la propriété relationnelle)
    #[Map(source: 'artisanProfile.displayName')]
    public ?string $displayName = null;

    #[Map(source: 'artisanProfile.phone')]
    public ?string $phone = null;

    #[Map(source: 'artisanProfile.bio')]
    public ?string $bio = null;

    #[Map(source: 'artisanProfile.wilaya')]
    public ?string $wilaya = null;

    #[Map(source: 'artisanProfile.commune')]
    public ?string $commune = null;

    // enum KycStatus -> string value, fallback 'pending'
    #[Map(source: 'artisanProfile.kycStatus', transform: [self::class, 'normalizeKyc'])]
    public string $kycStatus = 'pending';

    public static function normalizeKyc(mixed $value): string
    {
        // $value peut être null ou App\Enum\KycStatus
        return \is_object($value) && \method_exists($value, 'value') ? $value->value : 'pending';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'artisan_profile' => [
                'display_name' => $this->displayName,
                'phone' => $this->phone,
                'bio' => $this->bio,
                'wilaya' => $this->wilaya,
                'commune' => $this->commune,
                'kyc_status' => $this->kycStatus,
            ],
        ];
    }
}
