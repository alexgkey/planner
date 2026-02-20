<?php

namespace App\Security\Voter;

use App\Entity\Event;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

class EventVoter extends Voter
{
    public const EDIT = 'EVENT_EDIT';
    public const ADD_REPORT = 'EVENT_ADD_REPORT';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::ADD_REPORT])
            && $subject instanceof Event;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // 1. Админ может всё
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // 2. Пользователь должен быть как минимум Директором, чтобы иметь право редактировать
        if (!$this->security->isGranted('ROLE_DIR')) {
            return false;
        }

        /** @var Event $event */
        $event = $subject;

        // 3. Проверяем, что у пользователя и у мероприятия есть отдел
        if (null === $user->getDepartment() || null === $event->getDepartment()) {
            return false;
        }

        // 4. Главное правило: разрешаем доступ, если пользователь из того же отдела
        return $user->getDepartment()->getId() === $event->getDepartment()->getId();
    }
}
