<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251003092757 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE refresh_token DROP CONSTRAINT fk_c74f21955cdcfef4');
        $this->addSql('DROP INDEX idx_c74f21955cdcfef4');
        $this->addSql('ALTER TABLE refresh_token ADD rotated_from_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE refresh_token DROP rotated_form_id');
        $this->addSql('ALTER TABLE refresh_token ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE refresh_token RENAME COLUMN revoke_at TO revoked_at');
        $this->addSql('COMMENT ON COLUMN refresh_token.rotated_from_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN refresh_token.id IS \'(DC2Type:ulid)\'');
        $this->addSql('ALTER TABLE refresh_token ADD CONSTRAINT FK_C74F21957BE4BC82 FOREIGN KEY (rotated_from_id) REFERENCES refresh_token (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C74F21957BE4BC82 ON refresh_token (rotated_from_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE refresh_token DROP CONSTRAINT FK_C74F21957BE4BC82');
        $this->addSql('DROP INDEX IDX_C74F21957BE4BC82');
        $this->addSql('ALTER TABLE refresh_token ADD rotated_form_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE refresh_token DROP rotated_from_id');
        $this->addSql('ALTER TABLE refresh_token ALTER id TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE refresh_token RENAME COLUMN revoked_at TO revoke_at');
        $this->addSql('COMMENT ON COLUMN refresh_token.id IS NULL');
        $this->addSql('ALTER TABLE refresh_token ADD CONSTRAINT fk_c74f21955cdcfef4 FOREIGN KEY (rotated_form_id) REFERENCES refresh_token (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_c74f21955cdcfef4 ON refresh_token (rotated_form_id)');
    }
}
