<?php

namespace App\Entity;

use App\Repository\EventReportRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventReportRepository::class)]
#[ORM\HasLifecycleCallbacks]
class EventReport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'report', targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $creator = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $lastEditor = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\OneToMany(mappedBy: 'report', targetEntity: Photo::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $photos;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $visitorsCount = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $participantsCount = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $disabledVisitorsCount = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $seniorsVisitorsCount = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $adultsVisitorsCount = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $youthVisitorsCount = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $childrenVisitorsCount = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $childrenAtRiskCount = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $smoParticipantsCount = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $smoFamiliesCount = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $youngFamiliesCount = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $volunteersCount = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $publicReportText = null;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(Event $event): static
    {
        $this->event = $event;

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

    public function getLastEditor(): ?User
    {
        return $this->lastEditor;
    }

    public function setLastEditor(?User $lastEditor): static
    {
        $this->lastEditor = $lastEditor;

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

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return Collection<int, Photo>
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(Photo $photo): static
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setReport($this);
        }

        return $this;
    }

    public function removePhoto(Photo $photo): static
    {
        if ($this->photos->removeElement($photo)) {
            // set the owning side to null (unless already changed)
            if ($photo->getReport() === $this) {
                $photo->setReport(null);
            }
        }

        return $this;
    }

    public function getVisitorsCount(): ?int
    {
        return $this->visitorsCount;
    }

    public function setVisitorsCount(?int $visitorsCount): static
    {
        $this->visitorsCount = $visitorsCount;

        return $this;
    }

    public function getParticipantsCount(): ?int
    {
        return $this->participantsCount;
    }

    public function setParticipantsCount(?int $participantsCount): static
    {
        $this->participantsCount = $participantsCount;

        return $this;
    }

    public function getDisabledVisitorsCount(): ?int
    {
        return $this->disabledVisitorsCount;
    }

    public function setDisabledVisitorsCount(?int $disabledVisitorsCount): static
    {
        $this->disabledVisitorsCount = $disabledVisitorsCount;

        return $this;
    }

    public function getSeniorsVisitorsCount(): ?int
    {
        return $this->seniorsVisitorsCount;
    }

    public function setSeniorsVisitorsCount(?int $seniorsVisitorsCount): static
    {
        $this->seniorsVisitorsCount = $seniorsVisitorsCount;

        return $this;
    }

    public function getAdultsVisitorsCount(): ?int
    {
        return $this->adultsVisitorsCount;
    }

    public function setAdultsVisitorsCount(?int $adultsVisitorsCount): static
    {
        $this->adultsVisitorsCount = $adultsVisitorsCount;

        return $this;
    }

    public function getYouthVisitorsCount(): ?int
    {
        return $this->youthVisitorsCount;
    }

    public function setYouthVisitorsCount(?int $youthVisitorsCount): static
    {
        $this->youthVisitorsCount = $youthVisitorsCount;

        return $this;
    }

    public function getChildrenVisitorsCount(): ?int
    {
        return $this->childrenVisitorsCount;
    }

    public function setChildrenVisitorsCount(?int $childrenVisitorsCount): static
    {
        $this->childrenVisitorsCount = $childrenVisitorsCount;

        return $this;
    }

    public function getChildrenAtRiskCount(): ?int
    {
        return $this->childrenAtRiskCount;
    }

    public function setChildrenAtRiskCount(?int $childrenAtRiskCount): static
    {
        $this->childrenAtRiskCount = $childrenAtRiskCount;

        return $this;
    }

    public function getSmoParticipantsCount(): ?int
    {
        return $this->smoParticipantsCount;
    }

    public function setSmoParticipantsCount(?int $smoParticipantsCount): static
    {
        $this->smoParticipantsCount = $smoParticipantsCount;

        return $this;
    }

    public function getSmoFamiliesCount(): ?int
    {
        return $this->smoFamiliesCount;
    }

    public function setSmoFamiliesCount(?int $smoFamiliesCount): static
    {
        $this->smoFamiliesCount = $smoFamiliesCount;

        return $this;
    }

    public function getYoungFamiliesCount(): ?int
    {
        return $this->youngFamiliesCount;
    }

    public function setYoungFamiliesCount(?int $youngFamiliesCount): static
    {
        $this->youngFamiliesCount = $youngFamiliesCount;

        return $this;
    }

    public function getVolunteersCount(): ?int
    {
        return $this->volunteersCount;
    }

    public function setVolunteersCount(?int $volunteersCount): static
    {
        $this->volunteersCount = $volunteersCount;

        return $this;
    }

    public function getPublicReportText(): ?string
    {
        return $this->publicReportText;
    }

    public function setPublicReportText(?string $publicReportText): static
    {
        $this->publicReportText = $publicReportText;

        return $this;
    }
}
