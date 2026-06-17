<?php

namespace App\Service;

use App\Entity\Employee;
use App\Entity\User;
use App\Security\Permissions\AppPermissions;
use Symfony\Bundle\SecurityBundle\Security;

class TimesheetAccessService
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    public function hasAccess(): bool
    {
        return $this->security->isGranted(AppPermissions::TIMESHEET_VIEW);
    }

    public function isAdmin(): bool
    {
        return $this->security->isGranted(AppPermissions::TIMESHEET_ADMIN);
    }

    public function canViewAllDepartments(): bool
    {
        return $this->security->isGranted(AppPermissions::TIMESHEET_VIEW_ANY)
            || $this->security->isGranted(AppPermissions::TIMESHEET_MANAGE_ANY)
            || $this->isAdmin();
    }

    public function canViewOwnDepartment(): bool
    {
        return $this->security->isGranted(AppPermissions::TIMESHEET_VIEW_DEPARTMENT)
            || $this->security->isGranted(AppPermissions::TIMESHEET_MANAGE_DEPARTMENT)
            || $this->canViewAllDepartments();
    }

    public function canManageOwn(): bool
    {
        return $this->security->isGranted(AppPermissions::TIMESHEET_MANAGE_OWN)
            || $this->security->isGranted(AppPermissions::TIMESHEET_MANAGE_DEPARTMENT)
            || $this->security->isGranted(AppPermissions::TIMESHEET_MANAGE_ANY)
            || $this->isAdmin();
    }

    public function canManageOwnDepartment(): bool
    {
        return $this->security->isGranted(AppPermissions::TIMESHEET_MANAGE_DEPARTMENT)
            || $this->security->isGranted(AppPermissions::TIMESHEET_MANAGE_ANY)
            || $this->isAdmin();
    }

    public function canManageAllDepartments(): bool
    {
        return $this->security->isGranted(AppPermissions::TIMESHEET_MANAGE_ANY)
            || $this->isAdmin();
    }

    public function canViewEmployee(User $user, Employee $employee): bool
    {
        if ($this->canViewAllDepartments()) {
            return true;
        }

        if ($this->canViewOwnDepartment() && $this->isSameDepartment($user, $employee)) {
            return true;
        }

        return $this->isOwnEmployee($user, $employee);
    }

    public function canEditEmployee(User $user, Employee $employee): bool
    {
        if ($this->canManageAllDepartments()) {
            return true;
        }

        if ($this->canManageOwnDepartment() && $this->isSameDepartment($user, $employee)) {
            return true;
        }

        return $this->canManageOwn() && $this->isOwnEmployee($user, $employee);
    }

    public function canEditDate(User $user, Employee $employee, \DateTimeImmutable $date): bool
    {
        if (!$this->canEditEmployee($user, $employee)) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        return $date->setTime(0, 0) >= new \DateTimeImmutable('today');
    }

    private function isOwnEmployee(User $user, Employee $employee): bool
    {
        return null !== $user->getEmployee() && $user->getEmployee()->getId() === $employee->getId();
    }

    private function isSameDepartment(User $user, Employee $employee): bool
    {
        $userDepartment = $user->getEmployee()?->getDepartment();
        $employeeDepartment = $employee->getDepartment();

        if (null === $userDepartment || null === $employeeDepartment) {
            return false;
        }

        return $userDepartment->getId() === $employeeDepartment->getId();
    }
}
