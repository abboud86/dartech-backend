<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251003091153 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE refresh_token ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE refresh_token ALTER rotated_form_id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN refresh_token.id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN refresh_token.rotated_form_id IS \'(DC2Type:ulid)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE refresh_token ALTER id TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE refresh_token ALTER rotated_form_id TYPE VARCHAR(255)');
        $this->addSql('COMMENT ON COLUMN refresh_token.id IS NULL');
        $this->addSql('COMMENT ON COLUMN refresh_token.rotated_form_id IS NULL');
    }
}
