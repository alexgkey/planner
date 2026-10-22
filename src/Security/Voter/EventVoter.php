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
use Psr\Log\LoggerInterface;

class EventVoter extends Voter
{
    // Эти атрибуты оставлены для совместимости с контроллерами и шаблонами.
    public const EDIT = 'EVENT_EDIT';
    public const ADD_REPORT = 'EVENT_ADD_REPORT';
    public const CANCEL = 'EVENT_CANCEL';

    public function __construct(
        private readonly Security $security,
        private readonly LoggerInterface $logger,
    )
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
            $this->logReportDenied($user, $event, 'event_cancelled');

            return false;
        }

        if (!$this->isReportEditableEvent($event)) {
            $this->logReportDenied($user, $event, 'event_date_is_in_future_or_missing');

            return false;
        }

        if ($this->security->isGranted(AppPermissions::EVENT_MANAGE_ANY)) {
            return true;
        }

        $hasOwnPermission = $this->security->isGranted(AppPermissions::EVENT_MANAGE_OWN);
        $sameDepartment = $this->isInOwnDepartment($user, $event);

        if (!$hasOwnPermission) {
            $this->logReportDenied($user, $event, 'missing_event_manage_own_permission');
        } elseif (!$sameDepartment) {
            $this->logReportDenied($user, $event, 'different_or_missing_department');
        }

        return $hasOwnPermission && $sameDepartment;
    }

    private function logReportDenied(User $user, Event $event, string $reason): void
    {
        $this->logger->warning('Access denied while adding event report.', [
            'reason' => $reason,
            'event_id' => $event->getId(),
            'event_status' => $event->getStatus()->value,
            'event_date' => $event->getDate()?->format('Y-m-d'),
            'event_department_id' => $event->getDepartment()?->getId(),
            'user_id' => $user->getId(),
            'user_email' => $user->getUserIdentifier(),
            'user_department_id' => $user->getEmployee()?->getDepartment()?->getId(),
        ]);
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
