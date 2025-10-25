<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251024155026 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking ADD status_marking VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE booking DROP status');
        $this->addSql('ALTER TABLE booking ALTER communication_channel TYPE VARCHAR(50)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE booking ADD status VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE booking DROP status_marking');
        $this->addSql('ALTER TABLE booking ALTER communication_channel TYPE VARCHAR(32)');
    }
}
