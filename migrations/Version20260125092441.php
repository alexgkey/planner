<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260125092441 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE event (id INT AUTO_INCREMENT NOT NULL, date DATE DEFAULT NULL, venue VARCHAR(255) DEFAULT NULL, title VARCHAR(255) NOT NULL, responsible VARCHAR(255) DEFAULT NULL, planned_visitors SMALLINT DEFAULT NULL, note LONGTEXT DEFAULT NULL, is_active TINYINT NOT NULL, create_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, creator_id INT DEFAULT NULL, department_id INT DEFAULT NULL, INDEX IDX_3BAE0AA761220EA6 (creator_id), INDEX IDX_3BAE0AA7AE80F5DF (department_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA761220EA6 FOREIGN KEY (creator_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7AE80F5DF FOREIGN KEY (department_id) REFERENCES department (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA761220EA6');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7AE80F5DF');
        $this->addSql('DROP TABLE event');
    }
}
