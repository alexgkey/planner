<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313091500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Добавляет время проведения мероприятия';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event ADD event_time TIME DEFAULT NULL AFTER date');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event DROP event_time');
    }
}