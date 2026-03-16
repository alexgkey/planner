<?php

namespace App\Repository;

use App\Entity\Department;
use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * @return Event[]
     */
    public function findActiveByDepartment(?Department $department = null): array
    {
        $queryBuilder = $this->createQueryBuilder('e')
            ->andWhere('e.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('e.date', 'ASC')
            ->addOrderBy('e.time', 'ASC');

        if (null !== $department) {
            $queryBuilder
                ->andWhere('e.department = :department')
                ->setParameter('department', $department);
        }

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Event[]
     */
    public function findEventsNeedingReportReminder(\DateTimeImmutable $runAt, int $windowDays): array
    {
        $todayStart = $runAt->setTime(0, 0);
        $oldestRelevantDate = $todayStart->modify(sprintf('-%d days', $windowDays));

        return $this->createQueryBuilder('e')
            ->leftJoin('e.report', 'r')
            ->leftJoin('e.creator', 'creator')
            ->andWhere('e.isActive = :active')
            ->andWhere('r.id IS NULL')
            ->andWhere('e.status != :cancelled')
            ->andWhere('e.date IS NOT NULL')
            ->andWhere('e.date >= :oldestRelevantDate')
            ->andWhere('e.date <= :today')
            ->andWhere('creator.id IS NOT NULL')
            ->andWhere('creator.isActive = :creatorIsActive')
            ->andWhere('e.reportReminderLastSentAt IS NULL OR e.reportReminderLastSentAt < :todayStart')
            ->setParameter('active', true)
            ->setParameter('cancelled', 'cancelled')
            ->setParameter('oldestRelevantDate', $oldestRelevantDate)
            ->setParameter('today', $runAt)
            ->setParameter('creatorIsActive', true)
            ->setParameter('todayStart', $todayStart)
            ->orderBy('e.date', 'ASC')
            ->addOrderBy('e.time', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
