<?php

namespace App\Entity;

use App\Repository\EmployeeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmployeeRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Employee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $fio = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Department $department = null;

    #[ORM\OneToOne(mappedBy: 'employee', cascade: ['persist', 'remove'])]
    private ?User $userAccount = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $workStartDate = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, EmployeeAward>
     */
    #[ORM\OneToMany(mappedBy: 'employee', targetEntity: EmployeeAward::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['year' => 'DESC', 'id' => 'DESC'])]
    private Collection $awards;

    /**
     * @var Collection<int, EmployeeEducation>
     */
    #[ORM\OneToMany(mappedBy: 'employee', targetEntity: EmployeeEducation::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['year' => 'DESC', 'id' => 'DESC'])]
    private Collection $educations;

    /**
     * @var Collection<int, EmployeeTraining>
     */
    #[ORM\OneToMany(mappedBy: 'employee', targetEntity: EmployeeTraining::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['year' => 'DESC', 'id' => 'DESC'])]
    private Collection $trainings;

    /**
     * @var Collection<int, EmployeeAchievement>
     */
    #[ORM\OneToMany(mappedBy: 'employee', targetEntity: EmployeeAchievement::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['year' => 'DESC', 'id' => 'DESC'])]
    private Collection $achievements;

    public function __construct()
    {
        $this->awards = new ArrayCollection();
        $this->educations = new ArrayCollection();
        $this->trainings = new ArrayCollection();
        $this->achievements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFio(): ?string
    {
        return $this->fio;
    }

    public function setFio(string $fio): static
    {
        $this->fio = $fio;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

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

    public function getUserAccount(): ?User
    {
        return $this->userAccount;
    }

    public function setUserAccount(User $userAccount): static
    {
        if ($userAccount->getEmployee() !== $this) {
            $userAccount->setEmployee($this);
        }

        $this->userAccount = $userAccount;

        return $this;
    }

    public function getWorkStartDate(): ?\DateTimeInterface
    {
        return $this->workStartDate;
    }

    public function setWorkStartDate(?\DateTimeInterface $workStartDate): static
    {
        $this->workStartDate = $workStartDate;

        return $this;
    }

    public function getCurrentWorkExperienceLabel(): ?string
    {
        if (null === $this->workStartDate) {
            return null;
        }

        $startDate = \DateTimeImmutable::createFromInterface($this->workStartDate)->setTime(0, 0);
        $today = new \DateTimeImmutable('today');

        if ($startDate > $today) {
            return '0 г. 0 мес.';
        }

        $interval = $startDate->diff($today);

        return sprintf('%d г. %d мес.', $interval->y, $interval->m);
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

    /**
     * @return Collection<int, EmployeeAward>
     */
    public function getAwards(): Collection
    {
        return $this->awards;
    }

    public function addAward(EmployeeAward $award): static
    {
        if (!$this->awards->contains($award)) {
            $this->awards->add($award);
            $award->setEmployee($this);
        }

        return $this;
    }

    public function removeAward(EmployeeAward $award): static
    {
        if ($this->awards->removeElement($award) && $award->getEmployee() === $this) {
            $award->setEmployee(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, EmployeeEducation>
     */
    public function getEducations(): Collection
    {
        return $this->educations;
    }

    public function addEducation(EmployeeEducation $education): static
    {
        if (!$this->educations->contains($education)) {
            $this->educations->add($education);
            $education->setEmployee($this);
        }

        return $this;
    }

    public function removeEducation(EmployeeEducation $education): static
    {
        if ($this->educations->removeElement($education) && $education->getEmployee() === $this) {
            $education->setEmployee(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, EmployeeTraining>
     */
    public function getTrainings(): Collection
    {
        return $this->trainings;
    }

    public function addTraining(EmployeeTraining $training): static
    {
        if (!$this->trainings->contains($training)) {
            $this->trainings->add($training);
            $training->setEmployee($this);
        }

        return $this;
    }

    public function removeTraining(EmployeeTraining $training): static
    {
        if ($this->trainings->removeElement($training) && $training->getEmployee() === $this) {
            $training->setEmployee(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, EmployeeAchievement>
     */
    public function getAchievements(): Collection
    {
        return $this->achievements;
    }

    public function addAchievement(EmployeeAchievement $achievement): static
    {
        if (!$this->achievements->contains($achievement)) {
            $this->achievements->add($achievement);
            $achievement->setEmployee($this);
        }

        return $this;
    }

    public function removeAchievement(EmployeeAchievement $achievement): static
    {
        if ($this->achievements->removeElement($achievement) && $achievement->getEmployee() === $this) {
            $achievement->setEmployee(null);
        }

        return $this;
    }
}