<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250930161239 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE artisan_profile (id SERIAL NOT NULL, user_id UUID NOT NULL, display_name VARCHAR(80) NOT NULL, phone VARCHAR(20) NOT NULL, bio TEXT DEFAULT NULL, wilaya VARCHAR(64) NOT NULL, commune VARCHAR(64) NOT NULL, kyc_status VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B5D83F1EA76ED395 ON artisan_profile (user_id)');
        $this->addSql('COMMENT ON COLUMN artisan_profile.user_id IS \'(DC2Type:ulid)\'');
        $this->addSql('CREATE TABLE "user" (id UUID NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');
        $this->addSql('COMMENT ON COLUMN "user".id IS \'(DC2Type:ulid)\'');
        $this->addSql('ALTER TABLE artisan_profile ADD CONSTRAINT FK_B5D83F1EA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE artisan_profile DROP CONSTRAINT FK_B5D83F1EA76ED395');
        $this->addSql('DROP TABLE artisan_profile');
        $this->addSql('DROP TABLE "user"');
    }
}
