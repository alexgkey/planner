<?php

namespace App\Security\Voter;

use App\Entity\Department;
use App\Entity\User;
use App\Security\Permissions\AppPermissions;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DepartmentVoter extends Voter
{
    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$subject instanceof Department) {
            return false;
        }

        return in_array($attribute, [
            AppPermissions::DEPARTMENT_VIEW,
            AppPermissions::DEPARTMENT_MANAGE_OWN,
            AppPermissions::DEPARTMENT_MANAGE_ANY,
            AppPermissions::DEPARTMENT_ADMIN,
        ], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Department $department */
        $department = $subject;

        if ($this->security->isGranted(AppPermissions::DEPARTMENT_ADMIN)) {
            return true;
        }

        return match ($attribute) {
            AppPermissions::DEPARTMENT_VIEW => $this->canView($user, $department),
            AppPermissions::DEPARTMENT_MANAGE_OWN => $this->canManageOwn($user, $department),
            AppPermissions::DEPARTMENT_MANAGE_ANY => $this->canManageAny(),
            AppPermissions::DEPARTMENT_ADMIN => false,
            default => false,
        };
    }

    private function canView(User $user, Department $department): bool
    {
        if ($this->security->isGranted(AppPermissions::DEPARTMENT_MANAGE_ANY)) {
            return true;
        }

        return $this->isOwnDepartment($user, $department);
    }

    private function canManageOwn(User $user, Department $department): bool
    {
        if (!$this->security->isGranted(AppPermissions::DEPARTMENT_MANAGE_OWN)) {
            return false;
        }

        return $this->isOwnDepartment($user, $department);
    }

    private function canManageAny(): bool
    {
        return $this->security->isGranted(AppPermissions::DEPARTMENT_MANAGE_ANY);
    }

    private function isOwnDepartment(User $user, Department $department): bool
    {
        $employeeDepartment = $user->getEmployee()?->getDepartment();
        if (null === $employeeDepartment || null === $department->getId()) {
            return false;
        }

        return $employeeDepartment->getId() === $department->getId();
    }
}