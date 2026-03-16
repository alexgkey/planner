<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Добавляет поля для ежедневных напоминаний об отчете мероприятия';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event ADD report_reminder_last_sent_at DATETIME DEFAULT NULL, ADD report_reminder_sent_count SMALLINT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event DROP report_reminder_last_sent_at, DROP report_reminder_sent_count');
    }
}