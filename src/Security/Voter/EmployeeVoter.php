<?php

namespace App\Security\Voter;

use App\Entity\Employee;
use App\Entity\User;
use App\Security\Permissions\AppPermissions;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EmployeeVoter extends Voter
{
    public const EDIT = 'EMPLOYEE_EDIT';
    public const DELETE = 'EMPLOYEE_DELETE';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$subject instanceof Employee) {
            return false;
        }

        return in_array($attribute, [
            AppPermissions::EMPLOYEE_VIEW,
            AppPermissions::EMPLOYEE_VIEW_DEPARTMENT,
            AppPermissions::EMPLOYEE_VIEW_ANY,
            AppPermissions::EMPLOYEE_MANAGE_OWN,
            AppPermissions::EMPLOYEE_MANAGE_DEPARTMENT,
            AppPermissions::EMPLOYEE_MANAGE_ANY,
            AppPermissions::EMPLOYEE_ADMIN,
            self::EDIT,
            self::DELETE,
        ], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Employee $employee */
        $employee = $subject;

        if ($this->security->isGranted(AppPermissions::EMPLOYEE_ADMIN)) {
            return true;
        }

        return match ($attribute) {
            AppPermissions::EMPLOYEE_VIEW => $this->canView($user, $employee),
            AppPermissions::EMPLOYEE_VIEW_DEPARTMENT => $this->canViewDepartment($user, $employee),
            AppPermissions::EMPLOYEE_VIEW_ANY => $this->canViewAny(),
            AppPermissions::EMPLOYEE_MANAGE_OWN => $this->canManageOwn($user, $employee),
            AppPermissions::EMPLOYEE_MANAGE_DEPARTMENT => $this->canManageDepartment($user, $employee),
            AppPermissions::EMPLOYEE_MANAGE_ANY => $this->canManageAny($user, $employee),
            self::EDIT => $this->canManageOwn($user, $employee)
                || $this->canManageDepartment($user, $employee)
                || $this->canManageAny($user, $employee),
            self::DELETE => $this->canManageDepartment($user, $employee)
                || $this->canManageAny($user, $employee),
            default => false,
        };
    }

    private function canView(User $user, Employee $employee): bool
    {
        if ($this->canViewAny()) {
            return true;
        }

        if ($this->canViewDepartment($user, $employee)) {
            return true;
        }

        return $this->isOwnEmployee($user, $employee);
    }

    private function canViewDepartment(User $user, Employee $employee): bool
    {
        if (
            !$this->security->isGranted(AppPermissions::EMPLOYEE_VIEW_DEPARTMENT)
            && !$this->security->isGranted(AppPermissions::EMPLOYEE_MANAGE_DEPARTMENT)
            && !$this->security->isGranted(AppPermissions::EMPLOYEE_MANAGE_ANY)
        ) {
            return false;
        }

        return $this->isSameDepartment($user, $employee);
    }

    private function canViewAny(): bool
    {
        return $this->security->isGranted(AppPermissions::EMPLOYEE_VIEW_ANY)
            || $this->security->isGranted(AppPermissions::EMPLOYEE_MANAGE_ANY);
    }

    private function canManageOwn(User $user, Employee $employee): bool
    {
        return $this->security->isGranted(AppPermissions::EMPLOYEE_MANAGE_OWN)
            && $this->isOwnEmployee($user, $employee);
    }

    private function canManageDepartment(User $user, Employee $employee): bool
    {
        if (!$this->security->isGranted(AppPermissions::EMPLOYEE_MANAGE_DEPARTMENT)) {
            return false;
        }

        if ($this->isOwnEmployee($user, $employee)) {
            return false;
        }

        return $this->isSameDepartment($user, $employee);
    }

    private function canManageAny(User $user, Employee $employee): bool
    {
        if (!$this->security->isGranted(AppPermissions::EMPLOYEE_MANAGE_ANY)) {
            return false;
        }

        return !$this->isOwnEmployee($user, $employee);
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