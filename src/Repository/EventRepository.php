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
            ->orderBy('e.date', 'ASC');

        if (null !== $department) {
            $queryBuilder
                ->andWhere('e.department = :department')
                ->setParameter('department', $department);
        }

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }
}
