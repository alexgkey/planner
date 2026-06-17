<?php

namespace App\Entity;

use App\Entity\Enum\TimesheetStatus;
use App\Repository\TimesheetEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TimesheetEntryRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(
    name: 'timesheet_entry',
    uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_timesheet_employee_date', columns: ['employee_id', 'work_date'])]
)]
class TimesheetEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Employee $employee = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $workDate = null;

    #[ORM\Column(type: 'string', length: 20, enumType: TimesheetStatus::class)]
    private TimesheetStatus $status = TimesheetStatus::WORK;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function setEmployee(Employee $employee): static
    {
        $this->employee = $employee;

        return $this;
    }

    public function getWorkDate(): ?\DateTimeImmutable
    {
        return $this->workDate;
    }

    public function setWorkDate(\DateTimeImmutable $workDate): static
    {
        $this->workDate = $workDate->setTime(0, 0);

        return $this;
    }

    public function getStatus(): TimesheetStatus
    {
        return $this->status;
    }

    public function setStatus(TimesheetStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function updateCreatedAt(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
