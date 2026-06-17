<?php

namespace App\Audit;

use App\Entity\AuditLog;
use App\Entity\Event;
use App\Entity\EventReport;
use App\Entity\Photo;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditLogger
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
    ) {
    }

    public function logCurrentUser(
        string $action,
        string $subjectType,
        ?int $subjectId,
        ?string $subjectLabel,
        ?array $changes = null,
        ?array $metadata = null,
    ): void {
        $user = $this->security->getUser();
        $this->log(
            $action,
            $user instanceof User ? $user : null,
            $subjectType,
            $subjectId,
            $subjectLabel,
            $changes,
            $metadata
        );
    }

    public function log(
        string $action,
        ?User $actor,
        string $subjectType,
        ?int $subjectId,
        ?string $subjectLabel,
        ?array $changes = null,
        ?array $metadata = null,
        ?string $actorEmail = null,
    ): void {
        $request = $this->requestStack->getCurrentRequest();

        $auditLog = (new AuditLog())
            ->setAction($action)
            ->setActorUser($actor)
            ->setActorEmail($actor?->getEmail() ?? $actorEmail)
            ->setSubjectType($subjectType)
            ->setSubjectId($subjectId)
            ->setSubjectLabel($subjectLabel)
            ->setChangesJson($this->cleanPayload($changes))
            ->setMetadataJson($this->cleanPayload($metadata))
            ->setRouteName($request?->attributes->getString('_route') ?: null)
            ->setIp($request?->getClientIp())
            ->setUserAgent($request?->headers->get('User-Agent'));

        $this->entityManager->persist($auditLog);
        $this->entityManager->flush();
    }

    public function snapshotEvent(Event $event): array
    {
        return [
            'title' => $event->getTitle(),
            'date' => $event->getDate()?->format('Y-m-d'),
            'time' => $event->getTime()?->format('H:i:s'),
            'venue' => $event->getVenue(),
            'responsible' => $event->getResponsible(),
            'plannedVisitors' => $event->getPlannedVisitors(),
            'note' => $event->getNote(),
            'departmentId' => $event->getDepartment()?->getId(),
            'creatorId' => $event->getCreator()?->getId(),
            'status' => $event->getStatus()->value,
            'isActive' => $event->isActive(),
            'eventLevel' => $event->getEventLevel()?->value,
            'onOffLine' => $event->getOnOffLine()?->value,
            'eventDirection' => $event->getEventDirection()?->value,
            'eventAccessibility' => $event->getEventAccessibility()?->value,
            'targetAudience' => $event->getTargetAudience()?->value,
            'interaction' => $event->getInteraction(),
        ];
    }

    public function snapshotEventReport(EventReport $report): array
    {
        $text = $report->getPublicReportText();

        return [
            'participantsCount' => $report->getParticipantsCount(),
            'visitorsCount' => $report->getVisitorsCount(),
            'disabledVisitorsCount' => $report->getDisabledVisitorsCount(),
            'seniorsVisitorsCount' => $report->getSeniorsVisitorsCount(),
            'adultsVisitorsCount' => $report->getAdultsVisitorsCount(),
            'youthVisitorsCount' => $report->getYouthVisitorsCount(),
            'childrenVisitorsCount' => $report->getChildrenVisitorsCount(),
            'mixedAudienceCount' => $report->getMixedAudienceCount(),
            'childrenAtRiskCount' => $report->getChildrenAtRiskCount(),
            'smoParticipantsCount' => $report->getSmoParticipantsCount(),
            'smoFamiliesCount' => $report->getSmoFamiliesCount(),
            'youngFamiliesCount' => $report->getYoungFamiliesCount(),
            'volunteersCount' => $report->getVolunteersCount(),
            'scenarioName' => $report->getScenarioName(),
            'originalScenarioName' => $report->getOriginalScenarioName(),
            'photoIds' => $this->collectPhotoIds($report),
            'hasPublicReportText' => null !== $text && '' !== trim($text),
            'publicReportTextLength' => null !== $text ? mb_strlen($text) : 0,
        ];
    }

    public function snapshotPhoto(Photo $photo): array
    {
        return [
            'photoId' => $photo->getId(),
            'imageName' => $photo->getImageName(),
        ];
    }

    public function buildDiff(array $before, array $after): array
    {
        $diff = [];
        foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $key) {
            $beforeValue = $before[$key] ?? null;
            $afterValue = $after[$key] ?? null;

            if ($this->valuesAreEqual($beforeValue, $afterValue)) {
                continue;
            }

            $diff[$key] = [
                'from' => $beforeValue,
                'to' => $afterValue,
            ];
        }

        return $diff;
    }

    private function collectPhotoIds(EventReport $report): array
    {
        $photoIds = [];
        foreach ($report->getPhotos() as $photo) {
            if (null !== $photo->getId()) {
                $photoIds[] = $photo->getId();
            }
        }

        sort($photoIds);

        return $photoIds;
    }

    private function valuesAreEqual(mixed $left, mixed $right): bool
    {
        return $this->normalizeComparable($left) === $this->normalizeComparable($right);
    }

    private function normalizeComparable(mixed $value): mixed
    {
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeComparable($item);
            }

            return $normalized;
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        return $value;
    }

    private function cleanPayload(?array $payload): ?array
    {
        if (null === $payload || [] === $payload) {
            return null;
        }

        return $payload;
    }
}
