<?php

namespace App\Entity;

use App\Entity\Enum\EventReportPublicationPlatform;
use App\Entity\Enum\EventReportPublicationStatus;
use App\Repository\EventReportPublicationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventReportPublicationRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'event_report_publication')]
#[ORM\UniqueConstraint(name: 'uniq_report_platform', columns: ['event_report_id', 'platform'])]
class EventReportPublication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: EventReport::class, inversedBy: 'publications')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?EventReport $eventReport = null;

    #[ORM\Column(type: 'string', enumType: EventReportPublicationPlatform::class)]
    private EventReportPublicationPlatform $platform = EventReportPublicationPlatform::TELEGRAM;

    #[ORM\Column(type: 'string', enumType: EventReportPublicationStatus::class)]
    private EventReportPublicationStatus $status = EventReportPublicationStatus::DRAFT;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $sourceText = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $preparedText = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $aiProcessedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $skippedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $externalMessageId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $lastEditedBy = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventReport(): ?EventReport
    {
        return $this->eventReport;
    }

    public function setEventReport(?EventReport $eventReport): static
    {
        $this->eventReport = $eventReport;

        return $this;
    }

    public function getPlatform(): EventReportPublicationPlatform
    {
        return $this->platform;
    }

    public function setPlatform(EventReportPublicationPlatform $platform): static
    {
        $this->platform = $platform;

        return $this;
    }

    public function getStatus(): EventReportPublicationStatus
    {
        return $this->status;
    }

    public function setStatus(EventReportPublicationStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSourceText(): ?string
    {
        return $this->sourceText;
    }

    public function setSourceText(?string $sourceText): static
    {
        $this->sourceText = $sourceText;

        return $this;
    }

    public function getPreparedText(): ?string
    {
        return $this->preparedText;
    }

    public function setPreparedText(?string $preparedText): static
    {
        $this->preparedText = $preparedText;

        return $this;
    }

    public function getAiProcessedAt(): ?\DateTimeImmutable
    {
        return $this->aiProcessedAt;
    }

    public function setAiProcessedAt(?\DateTimeImmutable $aiProcessedAt): static
    {
        $this->aiProcessedAt = $aiProcessedAt;

        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getSkippedAt(): ?\DateTimeImmutable
    {
        return $this->skippedAt;
    }

    public function setSkippedAt(?\DateTimeImmutable $skippedAt): static
    {
        $this->skippedAt = $skippedAt;

        return $this;
    }

    public function getExternalMessageId(): ?string
    {
        return $this->externalMessageId;
    }

    public function setExternalMessageId(?string $externalMessageId): static
    {
        $this->externalMessageId = $externalMessageId;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getLastEditedBy(): ?User
    {
        return $this->lastEditedBy;
    }

    public function setLastEditedBy(?User $lastEditedBy): static
    {
        $this->lastEditedBy = $lastEditedBy;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function initializeCreatedAt(): void
    {
        $this->createdAt ??= new \DateTimeImmutable();
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function touchUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function hasPreparedText(): bool
    {
        return null !== $this->preparedText && '' !== trim($this->preparedText);
    }

    public function markAsPublished(?string $externalMessageId = null): static
    {
        $this->status = EventReportPublicationStatus::PUBLISHED;
        $this->publishedAt = new \DateTimeImmutable();
        $this->skippedAt = null;
        $this->externalMessageId = $externalMessageId;
        $this->errorMessage = null;

        return $this;
    }

    public function markAsSkipped(): static
    {
        $this->status = EventReportPublicationStatus::SKIPPED;
        $this->skippedAt = new \DateTimeImmutable();
        $this->publishedAt = null;
        $this->errorMessage = null;

        return $this;
    }

    public function markAsFailed(string $errorMessage): static
    {
        $this->status = EventReportPublicationStatus::FAILED;
        $this->errorMessage = $errorMessage;
        $this->publishedAt = null;

        return $this;
    }
}