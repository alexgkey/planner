<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Bundle\SecurityBundle\Security; // ИСПРАВЛЕНО
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

class UserVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            return false;
        }

        /** @var User $targetUser */
        $targetUser = $subject;

        // Нельзя редактировать/удалять самого себя
        if ($currentUser->getId() === $targetUser->getId()) {
            return false;
        }

        // Админ может все
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Менеджер может управлять Директорами и Пользователями
        if ($this->security->isGranted('ROLE_MANAGER')) {
            return !$this->hasRole($targetUser, 'ROLE_ADMIN') && !$this->hasRole($targetUser, 'ROLE_MANAGER');
        }

        // Директор может управлять только Пользователями
        if ($this->security->isGranted('ROLE_DIR')) {
            return !$this->hasRole($targetUser, 'ROLE_ADMIN') && !$this->hasRole($targetUser, 'ROLE_MANAGER') && !$this->hasRole($targetUser, 'ROLE_DIR');
        }

        return false;
    }

    private function hasRole(User $user, string $role): bool
    {
        return in_array($role, $user->getRoles());
    }
}
