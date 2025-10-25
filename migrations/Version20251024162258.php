<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251024162258 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE booking_timeline (id UUID NOT NULL, booking_id UUID NOT NULL, actor_id UUID DEFAULT NULL, from_status VARCHAR(50) DEFAULT NULL, to_status VARCHAR(50) NOT NULL, occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, context JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7DCA58EB3301C60 ON booking_timeline (booking_id)');
        $this->addSql('CREATE INDEX IDX_7DCA58EB10DAF24A ON booking_timeline (actor_id)');
        $this->addSql('COMMENT ON COLUMN booking_timeline.id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN booking_timeline.booking_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN booking_timeline.actor_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN booking_timeline.occurred_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE booking_timeline ADD CONSTRAINT FK_7DCA58EB3301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE booking_timeline ADD CONSTRAINT FK_7DCA58EB10DAF24A FOREIGN KEY (actor_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE booking_timeline DROP CONSTRAINT FK_7DCA58EB3301C60');
        $this->addSql('ALTER TABLE booking_timeline DROP CONSTRAINT FK_7DCA58EB10DAF24A');
        $this->addSql('DROP TABLE booking_timeline');
    }
}
