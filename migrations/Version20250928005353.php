<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250928005353 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id UUID NOT NULL, parent_id UUID DEFAULT NULL, name VARCHAR(128) NOT NULL, slug VARCHAR(160) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_64C19C1727ACA70 ON category (parent_id)');
        $this->addSql('CREATE INDEX idx_category_name ON category (name)');
        $this->addSql('CREATE UNIQUE INDEX uniq_category_slug ON category (slug)');
        $this->addSql('COMMENT ON COLUMN category.id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN category.parent_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN category.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN category.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE service_definition (id UUID NOT NULL, category_id UUID NOT NULL, name VARCHAR(128) NOT NULL, slug VARCHAR(160) NOT NULL, description TEXT DEFAULT NULL, attributes_schema JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_sd_category ON service_definition (category_id)');
        $this->addSql('CREATE INDEX idx_sd_name ON service_definition (name)');
        $this->addSql('CREATE UNIQUE INDEX uniq_service_definition_slug ON service_definition (slug)');
        $this->addSql('COMMENT ON COLUMN service_definition.id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN service_definition.category_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN service_definition.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN service_definition.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_definition ADD CONSTRAINT FK_FE61166C12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE category DROP CONSTRAINT FK_64C19C1727ACA70');
        $this->addSql('ALTER TABLE service_definition DROP CONSTRAINT FK_FE61166C12469DE2');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE service_definition');
    }
}
