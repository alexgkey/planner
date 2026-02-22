<?php

namespace App\Entity;

use App\Entity\Enum\EventAccessibility;
use App\Entity\Enum\EventDirection;
use App\Entity\Enum\EventLevel;
use App\Entity\Enum\OnOffLine;
use App\Entity\Enum\EventStatus;
use App\Entity\Enum\TargetAudience;
use App\Repository\EventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $date = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $venue = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $responsible = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $planned_visitors = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $creator = null;

    #[ORM\ManyToOne(inversedBy: 'events')]
    private ?Department $department = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'string', enumType: EventLevel::class)]
    private ?EventLevel $eventLevel = null;

    #[ORM\Column(type: 'string', enumType: OnOffLine::class)]
    private ?OnOffLine $onOffLine = null;

    #[ORM\Column(type: 'string', enumType: EventDirection::class)]
    private ?EventDirection $eventDirection = null;

    #[ORM\Column(type: 'string', enumType: EventAccessibility::class)]
    private ?EventAccessibility $eventAccessibility = null;

    #[ORM\Column(type: 'string', enumType: TargetAudience::class)]
    private ?TargetAudience $targetAudience = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $interaction = null;

    #[ORM\OneToOne(mappedBy: 'event', targetEntity: EventReport::class, cascade: ['persist', 'remove'])]
    private ?EventReport $report = null;

    #[ORM\Column(type: 'string', enumType: EventStatus::class)]
    private EventStatus $status = EventStatus::PLANNED;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(?\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getVenue(): ?string
    {
        return $this->venue;
    }

    public function setVenue(?string $venue): static
    {
        $this->venue = $venue;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getResponsible(): ?string
    {
        return $this->responsible;
    }

    public function setResponsible(string $responsible): static
    {
        $this->responsible = $responsible;

        return $this;
    }

    public function getPlannedVisitors(): ?int
    {
        return $this->planned_visitors;
    }

    public function setPlannedVisitors(?int $planned_visitors): static
    {
        $this->planned_visitors = $planned_visitors;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): static
    {
        $this->creator = $creator;

        return $this;
    }

    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    public function setDepartment(?Department $department): static
    {
        $this->department = $department;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreateAt(): ?\DateTimeImmutable
    {
        return $this->createAt;
    }

    #[ORM\PrePersist]
    public function setCreateAt(): void
    {
        $this->createAt = new \DateTimeImmutable();
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getEventLevel(): ?EventLevel
    {
        return $this->eventLevel;
    }

    public function setEventLevel(?EventLevel $eventLevel): static
    {
        $this->eventLevel = $eventLevel;

        return $this;
    }

    public function getOnOffLine(): ?OnOffLine
    {
        return $this->onOffLine;
    }

    public function setOnOffLine(?OnOffLine $onOffLine): static
    {
        $this->onOffLine = $onOffLine;

        return $this;
    }

    public function getEventDirection(): ?EventDirection
    {
        return $this->eventDirection;
    }

    public function setEventDirection(?EventDirection $eventDirection): static
    {
        $this->eventDirection = $eventDirection;

        return $this;
    }

    public function getEventAccessibility(): ?EventAccessibility
    {
        return $this->eventAccessibility;
    }

    public function setEventAccessibility(?EventAccessibility $eventAccessibility): static
    {
        $this->eventAccessibility = $eventAccessibility;

        return $this;
    }

    public function getTargetAudience(): ?TargetAudience
    {
        return $this->targetAudience;
    }

    public function setTargetAudience(?TargetAudience $targetAudience): static
    {
        $this->targetAudience = $targetAudience;

        return $this;
    }

    public function getInteraction(): ?string
    {
        return $this->interaction;
    }

    public function setInteraction(?string $interaction): static
    {
        $this->interaction = $interaction;

        return $this;
    }

    public function getReport(): ?EventReport
    {
        return $this->report;
    }

    public function setReport(EventReport $report): static
    {
        if ($report->getEvent() !== $this) {
            $report->setEvent($this);
        }

        $this->report = $report;

        return $this;
    }

    public function getStatus(): EventStatus
    {
        return $this->status;
    }

    public function setStatus(EventStatus $status): static
    {
        $this->status = $status;

        return $this;
    }
}
