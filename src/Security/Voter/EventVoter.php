<?php

namespace App\Security\Voter;

use App\Entity\Event;
use App\Entity\Enum\EventStatus;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

class EventVoter extends Voter
{
    public const EDIT = 'EVENT_EDIT';
    public const ADD_REPORT = 'EVENT_ADD_REPORT';
    public const CANCEL = 'EVENT_CANCEL';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::ADD_REPORT, self::CANCEL])
            && $subject instanceof Event;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Event $event */
        $event = $subject;

        // --- Правило доступа №1: Админ может всё, что не запрещено бизнес-логикой ---
        if ($this->security->isGranted('ROLE_ADMIN')) {
            // Бизнес-логика для админа: он тоже не может менять историю
            if ($attribute === self::EDIT || $attribute === self::CANCEL) {
                if ($event->getStatus() === EventStatus::COMPLETED || $event->getStatus() === EventStatus::CANCELLED) {
                    return false;
                }
            }
            return true;
        }

        // --- Правила для остальных пользователей ---
        switch ($attribute) {
            case self::EDIT:
            case self::CANCEL:
                // Бизнес-правило: нельзя редактировать/отменять завершенные, отмененные или прошедшие
                if ($event->getStatus() !== EventStatus::PLANNED || ($event->getDate() && $event->getDate() < new \DateTime('today'))) {
                    return false;
                }
                // Правило доступа: должен быть директором своего отдела
                return $this->isOwner($user, $event);

            case self::ADD_REPORT:
                // Бизнес-правило: нельзя добавлять отчет к отмененным
                if ($event->getStatus() === EventStatus::CANCELLED) {
                    return false;
                }
                // Правило доступа: должен быть директором своего отдела
                return $this->isOwner($user, $event);
        }

        return false;
    }

    /**
     * Проверяет, является ли пользователь Директором того же отдела, что и мероприятие.
     */
    private function isOwner(User $user, Event $event): bool
    {
        if (!$this->security->isGranted('ROLE_DIR')) {
            return false;
        }

        if (null === $user->getDepartment() || null === $event->getDepartment()) {
            return false;
        }

        return $user->getDepartment()->getId() === $event->getDepartment()->getId();
    }
}
