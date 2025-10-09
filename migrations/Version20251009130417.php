<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251009130417 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_status');
        $this->addSql('DROP INDEX uniq_artisan_slug');
        $this->addSql('ALTER INDEX idx_artisan_profile RENAME TO IDX_D5120FC3A02F3B25');
        $this->addSql('ALTER INDEX idx_service_definition RENAME TO IDX_D5120FC31A11393');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE INDEX idx_status ON artisan_service (status)');
        $this->addSql('CREATE UNIQUE INDEX uniq_artisan_slug ON artisan_service (artisan_profile_id, slug)');
        $this->addSql('ALTER INDEX idx_d5120fc3a02f3b25 RENAME TO idx_artisan_profile');
        $this->addSql('ALTER INDEX idx_d5120fc31a11393 RENAME TO idx_service_definition');
    }
}
