<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251007081024 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE artisan_service (id UUID NOT NULL, artisan_profile_id INT NOT NULL, service_definition_id UUID NOT NULL, title VARCHAR(160) NOT NULL, slug VARCHAR(180) NOT NULL, description TEXT DEFAULT NULL, unit_amount INT NOT NULL, currency VARCHAR(3) NOT NULL, status VARCHAR(255) NOT NULL, published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_artisan_profile ON artisan_service (artisan_profile_id)');
        $this->addSql('CREATE INDEX IDX_service_definition ON artisan_service (service_definition_id)');
        $this->addSql('CREATE INDEX IDX_status ON artisan_service (status)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_artisan_slug ON artisan_service (artisan_profile_id, slug)');
        $this->addSql('COMMENT ON COLUMN artisan_service.id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN artisan_service.service_definition_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN artisan_service.published_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN artisan_service.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN artisan_service.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE artisan_service ADD CONSTRAINT FK_D5120FC3A02F3B25 FOREIGN KEY (artisan_profile_id) REFERENCES artisan_profile (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE artisan_service ADD CONSTRAINT FK_D5120FC31A11393 FOREIGN KEY (service_definition_id) REFERENCES service_definition (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE artisan_service DROP CONSTRAINT FK_D5120FC3A02F3B25');
        $this->addSql('ALTER TABLE artisan_service DROP CONSTRAINT FK_D5120FC31A11393');
        $this->addSql('DROP TABLE artisan_service');
    }
}
