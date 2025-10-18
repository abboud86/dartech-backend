<?php
require __DIR__.'/../vendor/autoload.php';
foreach (\App\Enum\KycStatus::cases() as $c) {
    echo "KycStatus: {$c->name}={$c->value}\n";
}
foreach (\App\Enum\ArtisanServiceStatus::cases() as $c) {
    echo "ArtisanServiceStatus: {$c->name}={$c->value}\n";
}
