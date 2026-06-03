<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Restores event report publication storage for RSS feed publishing';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS event_report_publication (id INT AUTO_INCREMENT NOT NULL, event_report_id INT NOT NULL, created_by_id INT DEFAULT NULL, last_edited_by_id INT DEFAULT NULL, platform VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, source_text LONGTEXT DEFAULT NULL, prepared_text LONGTEXT DEFAULT NULL, ai_processed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', published_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', skipped_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', external_message_id VARCHAR(255) DEFAULT NULL, error_message LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C45034D11C9CBE1A (event_report_id), INDEX IDX_C45034D1B03A8386 (created_by_id), INDEX IDX_C45034D184B59594 (last_edited_by_id), UNIQUE INDEX uniq_report_platform (event_report_id, platform), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE event_report_publication ADD CONSTRAINT FK_C45034D11C9CBE1A FOREIGN KEY (event_report_id) REFERENCES event_report (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_report_publication ADD CONSTRAINT FK_C45034D1B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE event_report_publication ADD CONSTRAINT FK_C45034D184B59594 FOREIGN KEY (last_edited_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS event_report_publication');
    }
}
