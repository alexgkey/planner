<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260309113226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Этот метод сгенерирован автоматически, но изменен для переноса данных
        // Пожалуйста, убедитесь, что ваша база данных находится в состоянии ДО этой миграции

        // ШАГ 1: Создаем новую таблицу `employee`
        $this->addSql('CREATE TABLE employee (id INT AUTO_INCREMENT NOT NULL, fio VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, is_active TINYINT NOT NULL, create_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, department_id INT DEFAULT NULL, INDEX IDX_5D9F75A1AE80F5DF (department_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE employee ADD CONSTRAINT FK_5D9F75A1AE80F5DF FOREIGN KEY (department_id) REFERENCES department (id)');

        // ШАГ 2: Добавляем временную колонку `employee_id` в таблицу `user`
        $this->addSql('ALTER TABLE user ADD employee_id INT DEFAULT NULL');

        // ШАГ 3: Переносим данные из `user` в `employee`
        $this->addSql("INSERT INTO employee (fio, phone, department_id, is_active, create_at) SELECT fio, phone, department_id, is_active, create_at FROM `user`");

        // ШАГ 4: Связываем существующих пользователей с их новыми профилями сотрудников
        $this->addSql("UPDATE `user` u SET u.employee_id = (SELECT e.id FROM employee e WHERE e.fio = u.fio ORDER BY e.id DESC LIMIT 1)");

        // ШАГ 5: Делаем связь обязательной для всех пользователей
        $this->addSql('ALTER TABLE user MODIFY employee_id INT NOT NULL');

        // ШАГ 6: Добавляем внешние ключи и удаляем старые колонки
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6498C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6498C03F15C ON user (employee_id)');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY `FK_8D93D649AE80F5DF`');
        $this->addSql('DROP INDEX IDX_8D93D649AE80F5DF ON user');
        $this->addSql('ALTER TABLE user DROP department_id, DROP fio, DROP phone');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employee DROP FOREIGN KEY FK_5D9F75A1AE80F5DF');
        $this->addSql('DROP TABLE employee');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D6498C03F15C');
        $this->addSql('DROP INDEX UNIQ_8D93D6498C03F15C ON `user`');
        $this->addSql('ALTER TABLE `user` ADD department_id INT DEFAULT NULL, ADD fio VARCHAR(255) NOT NULL, ADD phone VARCHAR(255) NOT NULL, CHANGE create_at create_at DATETIME DEFAULT NULL, CHANGE employee_id employee_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT `FK_8D93D649AE80F5DF` FOREIGN KEY (department_id) REFERENCES department (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_8D93D649AE80F5DF ON `user` (department_id)');
        $this->addSql('ALTER TABLE `user` RENAME INDEX uniq_8d93d649e7927c74 TO UNIQ_IDENTIFIER_EMAIL');
    }
}
