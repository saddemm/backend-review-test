<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220224161501 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tables for actor, event, and repo, with type constraint on event table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE actor (id BIGINT NOT NULL, login VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, avatar_url VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE "event" (id BIGINT NOT NULL, actor_id BIGINT DEFAULT NULL, repo_id BIGINT DEFAULT NULL, type VARCHAR(255) NOT NULL, count INT NOT NULL, payload JSONB NOT NULL, create_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, comment TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3BAE0AA710DAF24A ON "event" (actor_id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7BD359B2D ON "event" (repo_id)');
        $this->addSql('CREATE INDEX IDX_EVENT_TYPE ON "event" (type)');
        $this->addSql('COMMENT ON COLUMN "event".type IS \'(DC2Type:EventType)\'');
        $this->addSql('COMMENT ON COLUMN "event".create_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE repo (id BIGINT NOT NULL, name VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE "event" ADD CONSTRAINT FK_3BAE0AA710DAF24A FOREIGN KEY (actor_id) REFERENCES actor (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "event" ADD CONSTRAINT FK_3BAE0AA7BD359B2D FOREIGN KEY (repo_id) REFERENCES repo (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Ajout de la contrainte CHECK pour la colonne type
        $this->addSql('ALTER TABLE "event" ADD CONSTRAINT event_type_check CHECK (type IN (\'COM\', \'MSG\', \'PR\', \'PUSH\', \'PULL_REQUEST_EVENT\', \'PULL_REQUEST_REVIEW_EVENT\', \'CREATE\', \'DELETE\', \'ISSUE_COMMENT\', \'ISSUE_EVENT\', \'FORK\', \'WATCH\', \'RELEASE\', \'COMMIT_COMMENT\', \'GOLLUM\', \'PULL_REQUEST_REVIEW_COMMENT\', \'MEMBER\', \'PUBLIC\'))');
    }

    public function down(Schema $schema): void
    {
        // Suppression de la contrainte CHECK et des tables
        $this->addSql('ALTER TABLE "event" DROP CONSTRAINT event_type_check');
        $this->addSql('ALTER TABLE "event" DROP CONSTRAINT FK_3BAE0AA710DAF24A');
        $this->addSql('ALTER TABLE "event" DROP CONSTRAINT FK_3BAE0AA7BD359B2D');
        $this->addSql('DROP TABLE actor');
        $this->addSql('DROP TABLE "event"');
        $this->addSql('DROP TABLE repo');
    }
}
