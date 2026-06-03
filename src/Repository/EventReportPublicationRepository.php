<?php

namespace App\Repository;

use App\Entity\EventReport;
use App\Entity\EventReportPublication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventReportPublication>
 *
 * @method EventReportPublication|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventReportPublication|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventReportPublication[]    findAll()
 * @method EventReportPublication[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventReportPublicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventReportPublication::class);
    }

    public function findPublishedForPlatform(string $platform, int $limit = 50): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.eventReport', 'r')
            ->addSelect('r')
            ->innerJoin('r.event', 'e')
            ->addSelect('e')
            ->leftJoin('r.photos', 'photos')
            ->addSelect('photos')
            ->andWhere('p.platform = :platform')
            ->andWhere('p.status = :status')
            ->setParameter('platform', $platform)
            ->setParameter('status', EventReportPublication::STATUS_PUBLISHED)
            ->orderBy('p.publishedAt', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findPublishedForReportAndPlatform(EventReport $report, string $platform): ?EventReportPublication
    {
        return $this->findOneBy([
            'eventReport' => $report,
            'platform' => $platform,
            'status' => EventReportPublication::STATUS_PUBLISHED,
        ]);
    }
}
