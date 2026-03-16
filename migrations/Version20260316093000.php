<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260316093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Добавляет расширенный профиль сотрудника: награды, образование, курсы, достижения и дату начала работы';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE employee ADD work_start_date DATE DEFAULT NULL');

        $this->addSql('CREATE TABLE employee_award (id INT AUTO_INCREMENT NOT NULL, employee_id INT NOT NULL, type VARCHAR(255) NOT NULL, ministry VARCHAR(255) NOT NULL, basis VARCHAR(255) DEFAULT NULL, year INT NOT NULL, INDEX IDX_3F834FD38C03F15C (employee_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE employee_education (id INT AUTO_INCREMENT NOT NULL, employee_id INT NOT NULL, type VARCHAR(255) NOT NULL, institution VARCHAR(255) NOT NULL, specialty VARCHAR(255) NOT NULL, year INT NOT NULL, INDEX IDX_7D6A46E68C03F15C (employee_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE employee_training (id INT AUTO_INCREMENT NOT NULL, employee_id INT NOT NULL, type VARCHAR(255) NOT NULL, institution VARCHAR(255) NOT NULL, year INT NOT NULL, hours INT NOT NULL, INDEX IDX_E33F7FB88C03F15C (employee_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE employee_achievement (id INT AUTO_INCREMENT NOT NULL, employee_id INT NOT NULL, title VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, year INT NOT NULL, INDEX IDX_218C1B758C03F15C (employee_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE employee_award ADD CONSTRAINT FK_3F834FD38C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE employee_education ADD CONSTRAINT FK_7D6A46E68C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE employee_training ADD CONSTRAINT FK_E33F7FB88C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE employee_achievement ADD CONSTRAINT FK_218C1B758C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE employee_award DROP FOREIGN KEY FK_3F834FD38C03F15C');
        $this->addSql('ALTER TABLE employee_education DROP FOREIGN KEY FK_7D6A46E68C03F15C');
        $this->addSql('ALTER TABLE employee_training DROP FOREIGN KEY FK_E33F7FB88C03F15C');
        $this->addSql('ALTER TABLE employee_achievement DROP FOREIGN KEY FK_218C1B758C03F15C');
        $this->addSql('DROP TABLE employee_award');
        $this->addSql('DROP TABLE employee_education');
        $this->addSql('DROP TABLE employee_training');
        $this->addSql('DROP TABLE employee_achievement');
        $this->addSql('ALTER TABLE employee DROP work_start_date');
    }
}