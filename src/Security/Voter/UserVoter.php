<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Security\Permissions\AppPermissions;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

class UserVoter extends Voter
{
    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Этот Voter работает только с разрешениями USER_* и объектами User
        return str_starts_with($attribute, 'ROLE_USER_') && $subject instanceof User;
    }

    // ИСПРАВЛЕННАЯ СИГНАТУРА МЕТОДА С ЧЕТЫРЬМЯ АРГУМЕНТАМИ
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            // Пользователь должен быть авторизован
            return false;
        }

        /** @var User $targetUser */
        $targetUser = $subject;

        // Супер-админ может все
        if ($this->security->isGranted(AppPermissions::USER_ADMIN)) {
            return true;
        }

        // Проверка, является ли целевой пользователь тем же, что и текущий
        $isSelf = $currentUser->getId() === $targetUser->getId();

        switch ($attribute) {
            case AppPermissions::USER_VIEW:
                // Разрешаем, если у пользователя есть право смотреть всех в отделе И они в одном отделе
                if ($this->security->isGranted(AppPermissions::USER_VIEW_ALL) && $this->isInSameDepartment($currentUser, $targetUser)) {
                    return true;
                }
                // Разрешаем, если пользователь смотрит сам себя
                return $isSelf;

            case AppPermissions::USER_MANAGE_OWN:
                // Разрешаем, только если пользователь редактирует сам себя
                return $isSelf;

            case AppPermissions::USER_MANAGE_ALL:
                // Разрешаем, если у пользователя есть это право И они в одном отделе
                return $this->isInSameDepartment($currentUser, $targetUser);
        }

        return false;
    }

    /**
     * Хелпер для проверки, находятся ли два пользователя в одном отделе.
     */
    private function isInSameDepartment(User $user1, User $user2): bool
    {
        $dept1 = $user1->getEmployee()?->getDepartment();
        $dept2 = $user2->getEmployee()?->getDepartment();

        // Если у кого-то нет отдела, считаем, что они не в одном отделе
        if (null === $dept1 || null === $dept2) {
            return false;
        }

        return $dept1->getId() === $dept2->getId();
    }
}
