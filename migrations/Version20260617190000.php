<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds timesheet storage for employee workday statuses';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE timesheet_entry (id INT AUTO_INCREMENT NOT NULL, employee_id INT NOT NULL, work_date DATE NOT NULL COMMENT '(DC2Type:date_immutable)', status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_94C4D0618C03F15C (employee_id), UNIQUE INDEX uniq_timesheet_employee_date (employee_id, work_date), INDEX idx_timesheet_work_date (work_date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE timesheet_entry ADD CONSTRAINT FK_94C4D0618C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE timesheet_entry');
    }
}
