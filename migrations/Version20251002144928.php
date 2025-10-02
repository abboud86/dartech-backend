<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251002144928 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE refresh_token (id VARCHAR(255) NOT NULL, rotated_form_id VARCHAR(255) DEFAULT NULL, owner_id UUID NOT NULL, token_hash VARCHAR(128) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, revoke_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C74F21955CDCFEF4 ON refresh_token (rotated_form_id)');
        $this->addSql('CREATE INDEX IDX_C74F21957E3C61F9 ON refresh_token (owner_id)');
        $this->addSql('COMMENT ON COLUMN refresh_token.owner_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN refresh_token.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN refresh_token.revoke_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN refresh_token.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE refresh_token ADD CONSTRAINT FK_C74F21955CDCFEF4 FOREIGN KEY (rotated_form_id) REFERENCES refresh_token (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE refresh_token ADD CONSTRAINT FK_C74F21957E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE refresh_token DROP CONSTRAINT FK_C74F21955CDCFEF4');
        $this->addSql('ALTER TABLE refresh_token DROP CONSTRAINT FK_C74F21957E3C61F9');
        $this->addSql('DROP TABLE refresh_token');
    }
}
