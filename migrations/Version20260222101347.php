<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222101347 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD status VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE event_report DROP results_assessment, DROP problems_analysis, DROP recommendations');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP status');
        $this->addSql('ALTER TABLE event_report ADD results_assessment LONGTEXT DEFAULT NULL, ADD problems_analysis LONGTEXT DEFAULT NULL, ADD recommendations LONGTEXT DEFAULT NULL');
    }
}
