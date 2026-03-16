<?php

namespace App\Entity;

use App\Entity\Enum\AwardType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class EmployeeAward
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'awards')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Employee $employee = null;

    #[ORM\Column(type: 'string', enumType: AwardType::class)]
    private ?AwardType $type = null;

    #[ORM\Column(length: 255)]
    private ?string $ministry = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $basis = null;

    #[ORM\Column]
    private ?int $year = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): static
    {
        $this->employee = $employee;

        return $this;
    }

    public function getType(): ?AwardType
    {
        return $this->type;
    }

    public function setType(?AwardType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getMinistry(): ?string
    {
        return $this->ministry;
    }

    public function setMinistry(string $ministry): static
    {
        $this->ministry = $ministry;

        return $this;
    }

    public function getBasis(): ?string
    {
        return $this->basis;
    }

    public function setBasis(?string $basis): static
    {
        $this->basis = $basis;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;

        return $this;
    }
}