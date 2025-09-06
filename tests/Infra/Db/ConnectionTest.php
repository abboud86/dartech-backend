<?php

namespace App\Tests\Infra\Db;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ConnectionTest extends KernelTestCase
{
    public function testDoctrineConnectionCanExecuteSelect1(): void
    {
        self::bootKernel();
        /** @var Connection $conn */
        $conn = static::getContainer()->get(Connection::class);

        $value = $conn->fetchOne('SELECT 1');
        self::assertSame(1, (int) $value);
    }
}
