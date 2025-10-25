<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251024150904 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking_timeline DROP CONSTRAINT fk_7dca58eb3301c60');
        $this->addSql('ALTER TABLE booking_timeline DROP CONSTRAINT fk_7dca58eb859b83ff');
        $this->addSql('DROP TABLE booking_timeline');
        $this->addSql('ALTER TABLE booking ALTER scheduled_at DROP NOT NULL');
        $this->addSql('ALTER TABLE booking ALTER status DROP NOT NULL');
        $this->addSql('ALTER TABLE booking ALTER status TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE booking ALTER communication_channel DROP NOT NULL');
        $this->addSql('ALTER TABLE booking ALTER communication_channel TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE booking ALTER updated_at DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE booking_timeline (id UUID NOT NULL, booking_id UUID NOT NULL, actor_user_id UUID DEFAULT NULL, transitioned_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, from_status VARCHAR(255) DEFAULT NULL, to_status VARCHAR(255) NOT NULL, reason VARCHAR(180) DEFAULT NULL, note TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_7dca58eb3301c60 ON booking_timeline (booking_id)');
        $this->addSql('CREATE INDEX idx_7dca58eb859b83ff ON booking_timeline (actor_user_id)');
        $this->addSql('COMMENT ON COLUMN booking_timeline.id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN booking_timeline.booking_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN booking_timeline.actor_user_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN booking_timeline.transitioned_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE booking_timeline ADD CONSTRAINT fk_7dca58eb3301c60 FOREIGN KEY (booking_id) REFERENCES booking (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE booking_timeline ADD CONSTRAINT fk_7dca58eb859b83ff FOREIGN KEY (actor_user_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE booking ALTER status SET NOT NULL');
        $this->addSql('ALTER TABLE booking ALTER status TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE booking ALTER communication_channel SET NOT NULL');
        $this->addSql('ALTER TABLE booking ALTER communication_channel TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE booking ALTER scheduled_at SET NOT NULL');
        $this->addSql('ALTER TABLE booking ALTER updated_at SET NOT NULL');
    }
}
