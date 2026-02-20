<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260218102327 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE event_report (id INT AUTO_INCREMENT NOT NULL, create_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, is_active TINYINT NOT NULL, visitors_count SMALLINT DEFAULT NULL, participants_count SMALLINT DEFAULT NULL, disabled_visitors_count SMALLINT DEFAULT NULL, seniors_visitors_count SMALLINT DEFAULT NULL, adults_visitors_count SMALLINT DEFAULT NULL, youth_visitors_count SMALLINT DEFAULT NULL, children_visitors_count SMALLINT DEFAULT NULL, children_at_risk_count SMALLINT DEFAULT NULL, smo_participants_count SMALLINT DEFAULT NULL, smo_families_count SMALLINT DEFAULT NULL, young_families_count SMALLINT DEFAULT NULL, volunteers_count SMALLINT DEFAULT NULL, results_assessment LONGTEXT DEFAULT NULL, problems_analysis LONGTEXT DEFAULT NULL, recommendations LONGTEXT DEFAULT NULL, public_report_text LONGTEXT DEFAULT NULL, event_id INT NOT NULL, creator_id INT NOT NULL, last_editor_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_F6B600D671F7E88B (event_id), INDEX IDX_F6B600D661220EA6 (creator_id), INDEX IDX_F6B600D67E5A734A (last_editor_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE event_report ADD CONSTRAINT FK_F6B600D671F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE event_report ADD CONSTRAINT FK_F6B600D661220EA6 FOREIGN KEY (creator_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE event_report ADD CONSTRAINT FK_F6B600D67E5A734A FOREIGN KEY (last_editor_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_report DROP FOREIGN KEY FK_F6B600D671F7E88B');
        $this->addSql('ALTER TABLE event_report DROP FOREIGN KEY FK_F6B600D661220EA6');
        $this->addSql('ALTER TABLE event_report DROP FOREIGN KEY FK_F6B600D67E5A734A');
        $this->addSql('DROP TABLE event_report');
    }
}
