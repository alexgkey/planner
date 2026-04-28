<?php

namespace App\Repository;

use App\Entity\EventReport;
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
}
