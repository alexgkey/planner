<?php

namespace App\Repository;

use App\Entity\EventReport;
use App\Entity\EventReportPublication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventReport>
 *
 * @method EventReport|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventReport|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventReport[]    findAll()
 * @method EventReport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventReport::class);
    }

    public function findReportsReadyForRssPublication(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): array {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.event', 'e')
            ->leftJoin(
                EventReportPublication::class,
                'p',
                'WITH',
                'p.eventReport = r AND p.platform = :platform AND p.status = :status'
            )
            ->leftJoin('r.photos', 'photos')
            ->addSelect('e', 'photos')
            ->andWhere('r.publicReportText IS NOT NULL')
            ->andWhere("TRIM(r.publicReportText) <> ''")
            ->andWhere('
                (r.updatedAt IS NOT NULL AND r.updatedAt >= :start AND r.updatedAt < :end)
                OR
                (r.updatedAt IS NULL AND r.createAt >= :start AND r.createAt < :end)
            ')
            ->andWhere('p.id IS NULL')
            ->setParameter('platform', EventReportPublication::PLATFORM_RSS)
            ->setParameter('status', EventReportPublication::STATUS_PUBLISHED)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('r.updatedAt', 'ASC')
            ->addOrderBy('r.createAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
