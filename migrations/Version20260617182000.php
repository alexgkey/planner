<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617182000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds audit log storage for events, reports, and authentication activity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, actor_user_id INT DEFAULT NULL, occurred_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', action VARCHAR(100) NOT NULL, actor_email VARCHAR(180) DEFAULT NULL, subject_type VARCHAR(50) NOT NULL, subject_id INT DEFAULT NULL, subject_label VARCHAR(255) DEFAULT NULL, changes_json JSON DEFAULT NULL, metadata_json JSON DEFAULT NULL, route_name VARCHAR(255) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(1024) DEFAULT NULL, INDEX IDX_23890D0DA76ED395 (actor_user_id), INDEX idx_audit_action_occurred_at (action, occurred_at), INDEX idx_audit_subject (subject_type, subject_id), INDEX idx_audit_occurred_at (occurred_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_23890D0DA76ED395 FOREIGN KEY (actor_user_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE audit_log');
    }
}
