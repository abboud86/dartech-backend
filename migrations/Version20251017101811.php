<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251017101811 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE media (id UUID NOT NULL, artisan_profile_id INT NOT NULL, public_url VARCHAR(2048) NOT NULL, is_public BOOLEAN DEFAULT true NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6A2CA10CA02F3B25 ON media (artisan_profile_id)');
        $this->addSql('CREATE INDEX idx_media_preview ON media (artisan_profile_id, is_public, created_at)');
        $this->addSql('COMMENT ON COLUMN media.id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN media.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10CA02F3B25 FOREIGN KEY (artisan_profile_id) REFERENCES artisan_profile (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_status ON artisan_service (status)');
        $this->addSql('CREATE INDEX IDX_published_at ON artisan_service (published_at)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_artisan_slug ON artisan_service (artisan_profile_id, slug)');
        $this->addSql('ALTER INDEX idx_d5120fc3a02f3b25 RENAME TO IDX_artisan_profile');
        $this->addSql('ALTER INDEX idx_d5120fc31a11393 RENAME TO IDX_service_definition');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE media DROP CONSTRAINT FK_6A2CA10CA02F3B25');
        $this->addSql('DROP TABLE media');
        $this->addSql('DROP INDEX IDX_status');
        $this->addSql('DROP INDEX IDX_published_at');
        $this->addSql('DROP INDEX UNIQ_artisan_slug');
        $this->addSql('ALTER INDEX idx_service_definition RENAME TO idx_d5120fc31a11393');
        $this->addSql('ALTER INDEX idx_artisan_profile RENAME TO idx_d5120fc3a02f3b25');
    }
}
