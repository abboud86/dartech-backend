<?php

namespace App\Enum;

enum ArtisanServiceStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';
}
