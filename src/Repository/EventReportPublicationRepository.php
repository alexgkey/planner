<?php

namespace App\Repository;

use App\Entity\Enum\EventReportPublicationStatus;
use App\Entity\EventReportPublication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventReportPublication>
 */
class EventReportPublicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventReportPublication::class);
    }

    /**
     * @return EventReportPublication[]
     */
    public function findVisibleQueue(int $visibleDays): array
    {
        $cutoff = (new \DateTimeImmutable('today'))->modify(sprintf('-%d days', max(0, $visibleDays)));

        return $this->createQueryBuilder('publication')
            ->addSelect('report', 'event', 'department', 'creator', 'photo')
            ->join('publication.eventReport', 'report')
            ->join('report.event', 'event')
            ->leftJoin('event.department', 'department')
            ->leftJoin('report.creator', 'creator')
            ->leftJoin('report.photos', 'photo')
            ->andWhere('event.isActive = true')
            ->andWhere('report.isActive = true')
            ->andWhere(
                '(publication.status IN (:openStatuses)) OR ' .
                '(publication.status = :publishedStatus AND publication.publishedAt >= :cutoff) OR ' .
                '(publication.status = :skippedStatus AND publication.skippedAt >= :cutoff)'
            )
            ->setParameter('openStatuses', [
                EventReportPublicationStatus::DRAFT,
                EventReportPublicationStatus::READY,
                EventReportPublicationStatus::FAILED,
            ])
            ->setParameter('publishedStatus', EventReportPublicationStatus::PUBLISHED)
            ->setParameter('skippedStatus', EventReportPublicationStatus::SKIPPED)
            ->setParameter('cutoff', $cutoff)
            ->orderBy('report.createAt', 'DESC')
            ->addOrderBy('publication.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}