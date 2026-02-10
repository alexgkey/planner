<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210022406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD interaction VARCHAR(255) DEFAULT NULL, CHANGE event_level event_level VARCHAR(255) NOT NULL, CHANGE on_off_line on_off_line VARCHAR(255) NOT NULL, CHANGE event_direction event_direction VARCHAR(255) NOT NULL, CHANGE event_accessibility event_accessibility VARCHAR(255) NOT NULL, CHANGE target_audience target_audience VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP interaction, CHANGE event_level event_level VARCHAR(255) DEFAULT NULL, CHANGE on_off_line on_off_line VARCHAR(255) DEFAULT NULL, CHANGE event_direction event_direction VARCHAR(255) DEFAULT NULL, CHANGE event_accessibility event_accessibility VARCHAR(255) DEFAULT NULL, CHANGE target_audience target_audience VARCHAR(255) DEFAULT NULL');
    }
}
