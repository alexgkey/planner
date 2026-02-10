<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210024643 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Заполняем пустые значения 'responsible' строкой 'Не указано'
        $this->addSql("UPDATE event SET responsible = 'Не указано' WHERE responsible IS NULL");

        // Заполняем пустые значения 'planned_visitors' нулем
        $this->addSql("UPDATE event SET planned_visitors = 0 WHERE planned_visitors IS NULL");
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event CHANGE responsible responsible VARCHAR(255) NOT NULL, CHANGE planned_visitors planned_visitors SMALLINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event CHANGE responsible responsible VARCHAR(255) DEFAULT NULL, CHANGE planned_visitors planned_visitors SMALLINT DEFAULT NULL');
    }
}
