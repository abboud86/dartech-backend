<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251017183711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE access_token (id UUID NOT NULL, owner_id UUID DEFAULT NULL, token_hash VARCHAR(128) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last_used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, scopes JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B6A2DD687E3C61F9 ON access_token (owner_id)');
        $this->addSql('COMMENT ON COLUMN access_token.id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN access_token.owner_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN access_token.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN access_token.revoked_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN access_token.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN access_token.last_used_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE artisan_profile (id SERIAL NOT NULL, user_id UUID NOT NULL, slug VARCHAR(26) NOT NULL, display_name VARCHAR(80) NOT NULL, phone VARCHAR(20) NOT NULL, bio TEXT DEFAULT NULL, wilaya VARCHAR(64) NOT NULL, commune VARCHAR(64) NOT NULL, kyc_status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B5D83F1E989D9B62 ON artisan_profile (slug)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B5D83F1EA76ED395 ON artisan_profile (user_id)');
        $this->addSql('COMMENT ON COLUMN artisan_profile.user_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN artisan_profile.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN artisan_profile.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE artisan_service (id UUID NOT NULL, artisan_profile_id INT NOT NULL, service_definition_id UUID NOT NULL, title VARCHAR(160) NOT NULL, slug VARCHAR(180) NOT NULL, description TEXT DEFAULT NULL, unit_amount INT NOT NULL, currency VARCHAR(3) NOT NULL, status VARCHAR(255) NOT NULL, published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_artisan_profile ON artisan_service (artisan_profile_id)');
        $this->addSql('CREATE INDEX IDX_service_definition ON artisan_service (service_definition_id)');
        $this->addSql('CREATE INDEX IDX_status ON artisan_service (status)');
        $this->addSql('CREATE INDEX IDX_published_at ON artisan_service (published_at)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_artisan_slug ON artisan_service (artisan_profile_id, slug)');
        $this->addSql('COMMENT ON COLUMN artisan_service.id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN artisan_service.service_definition_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN artisan_service.published_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN artisan_service.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN artisan_service.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE category (id UUID NOT NULL, parent_id UUID DEFAULT NULL, name VARCHAR(128) NOT NULL, slug VARCHAR(160) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_64C19C1727ACA70 ON category (parent_id)');
        $this->addSql('CREATE INDEX idx_category_name ON category (name)');
        $this->addSql('CREATE UNIQUE INDEX uniq_category_slug ON category (slug)');
        $this->addSql('COMMENT ON COLUMN category.id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN category.parent_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN category.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN category.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE media (id UUID NOT NULL, artisan_profile_id INT NOT NULL, public_url VARCHAR(2048) NOT NULL, is_public BOOLEAN DEFAULT true NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6A2CA10CA02F3B25 ON media (artisan_profile_id)');
        $this->addSql('CREATE INDEX idx_media_preview ON media (artisan_profile_id, is_public, created_at)');
        $this->addSql('COMMENT ON COLUMN media.id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN media.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE refresh_token (id UUID NOT NULL, rotated_from_id UUID DEFAULT NULL, owner_id UUID NOT NULL, token_hash VARCHAR(128) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C74F21957BE4BC82 ON refresh_token (rotated_from_id)');
        $this->addSql('CREATE INDEX IDX_C74F21957E3C61F9 ON refresh_token (owner_id)');
        $this->addSql('COMMENT ON COLUMN refresh_token.id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN refresh_token.rotated_from_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN refresh_token.owner_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN refresh_token.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN refresh_token.revoked_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN refresh_token.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE service_definition (id UUID NOT NULL, category_id UUID NOT NULL, name VARCHAR(128) NOT NULL, slug VARCHAR(160) NOT NULL, description TEXT DEFAULT NULL, attributes_schema JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_sd_category ON service_definition (category_id)');
        $this->addSql('CREATE INDEX idx_sd_name ON service_definition (name)');
        $this->addSql('CREATE UNIQUE INDEX uniq_service_definition_slug ON service_definition (slug)');
        $this->addSql('COMMENT ON COLUMN service_definition.id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN service_definition.category_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN service_definition.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN service_definition.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE "user" (id UUID NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');
        $this->addSql('COMMENT ON COLUMN "user".id IS \'(DC2Type:ulid)\'');
        $this->addSql('ALTER TABLE access_token ADD CONSTRAINT FK_B6A2DD687E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE artisan_profile ADD CONSTRAINT FK_B5D83F1EA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE artisan_service ADD CONSTRAINT FK_D5120FC3A02F3B25 FOREIGN KEY (artisan_profile_id) REFERENCES artisan_profile (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE artisan_service ADD CONSTRAINT FK_D5120FC31A11393 FOREIGN KEY (service_definition_id) REFERENCES service_definition (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10CA02F3B25 FOREIGN KEY (artisan_profile_id) REFERENCES artisan_profile (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE refresh_token ADD CONSTRAINT FK_C74F21957BE4BC82 FOREIGN KEY (rotated_from_id) REFERENCES refresh_token (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE refresh_token ADD CONSTRAINT FK_C74F21957E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE service_definition ADD CONSTRAINT FK_FE61166C12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE access_token DROP CONSTRAINT FK_B6A2DD687E3C61F9');
        $this->addSql('ALTER TABLE artisan_profile DROP CONSTRAINT FK_B5D83F1EA76ED395');
        $this->addSql('ALTER TABLE artisan_service DROP CONSTRAINT FK_D5120FC3A02F3B25');
        $this->addSql('ALTER TABLE artisan_service DROP CONSTRAINT FK_D5120FC31A11393');
        $this->addSql('ALTER TABLE category DROP CONSTRAINT FK_64C19C1727ACA70');
        $this->addSql('ALTER TABLE media DROP CONSTRAINT FK_6A2CA10CA02F3B25');
        $this->addSql('ALTER TABLE refresh_token DROP CONSTRAINT FK_C74F21957BE4BC82');
        $this->addSql('ALTER TABLE refresh_token DROP CONSTRAINT FK_C74F21957E3C61F9');
        $this->addSql('ALTER TABLE service_definition DROP CONSTRAINT FK_FE61166C12469DE2');
        $this->addSql('DROP TABLE access_token');
        $this->addSql('DROP TABLE artisan_profile');
        $this->addSql('DROP TABLE artisan_service');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE media');
        $this->addSql('DROP TABLE refresh_token');
        $this->addSql('DROP TABLE service_definition');
        $this->addSql('DROP TABLE "user"');
    }
}
