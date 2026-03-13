<?php

namespace App\Security\Voter;

use App\Entity\Event;
use App\Entity\Enum\EventStatus;
use App\Entity\User;
use App\Security\Permissions\AppPermissions;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EventVoter extends Voter
{
    // Эти атрибуты оставлены для совместимости с контроллерами и шаблонами.
    public const EDIT = 'EVENT_EDIT';
    public const ADD_REPORT = 'EVENT_ADD_REPORT';
    public const CANCEL = 'EVENT_CANCEL';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$subject instanceof Event) {
            return false;
        }

        return in_array($attribute, [
            AppPermissions::EVENT_VIEW,
            AppPermissions::EVENT_MANAGE_OWN,
            AppPermissions::EVENT_MANAGE_ANY,
            AppPermissions::EVENT_ADMIN,
            self::EDIT,
            self::ADD_REPORT,
            self::CANCEL,
        ], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Event $event */
        $event = $subject;

        if ($this->security->isGranted(AppPermissions::EVENT_ADMIN)) {
            return true;
        }

        return match ($attribute) {
            AppPermissions::EVENT_VIEW => $this->canView($user, $event),
            AppPermissions::EVENT_MANAGE_OWN => $this->canManageOwn($user, $event),
            AppPermissions::EVENT_MANAGE_ANY => $this->canManageAny($event),
            self::EDIT, self::CANCEL => $this->canManageAny($event) || $this->canManageOwn($user, $event),
            self::ADD_REPORT => $this->canAddReport($user, $event),
            default => false,
        };
    }

    private function canView(User $user, Event $event): bool
    {
        if ($this->security->isGranted(AppPermissions::EVENT_VIEW_ANY)) {
            return true;
        }

        return $this->isInOwnDepartment($user, $event);
    }

    private function canManageOwn(User $user, Event $event): bool
    {
        if (!$this->security->isGranted(AppPermissions::EVENT_MANAGE_OWN)) {
            return false;
        }

        return $this->isEditableEvent($event) && $this->isInOwnDepartment($user, $event);
    }

    private function canManageAny(Event $event): bool
    {
        if (!$this->security->isGranted(AppPermissions::EVENT_MANAGE_ANY)) {
            return false;
        }

        return $this->isEditableEvent($event);
    }

    private function canAddReport(User $user, Event $event): bool
    {
        if ($event->getStatus() === EventStatus::CANCELLED) {
            return false;
        }

        if (!$this->isReportEditableEvent($event)) {
            return false;
        }

        if ($this->security->isGranted(AppPermissions::EVENT_MANAGE_ANY)) {
            return true;
        }

        return $this->security->isGranted(AppPermissions::EVENT_MANAGE_OWN)
            && $this->isInOwnDepartment($user, $event);
    }

    private function isInOwnDepartment(User $user, Event $event): bool
    {
        $employee = $user->getEmployee();
        if (null === $employee || null === $employee->getDepartment() || null === $event->getDepartment()) {
            return false;
        }

        return $employee->getDepartment()->getId() === $event->getDepartment()->getId();
    }

    private function isEditableEvent(Event $event): bool
    {
        if ($event->getStatus() !== EventStatus::PLANNED) {
            return false;
        }

        $eventDate = $event->getDate();
        if (null === $eventDate) {
            return true;
        }

        // Ограничение остается по календарной дате, а не по часу мероприятия.
        return $eventDate >= new \DateTime('today');
    }

    private function isReportEditableEvent(Event $event): bool
    {
        $eventDate = $event->getDate();
        if (null === $eventDate) {
            return false;
        }

        // Отчет, как и раньше, доступен в день мероприятия и позже.
        return $eventDate <= new \DateTime('today');
    }
}