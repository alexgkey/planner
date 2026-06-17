<?php

namespace App\Repository;

use App\Entity\TimesheetEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TimesheetEntry>
 */
class TimesheetEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimesheetEntry::class);
    }

    /**
     * @param int[] $employeeIds
     *
     * @return TimesheetEntry[]
     */
    public function findForEmployeesAndPeriod(array $employeeIds, \DateTimeImmutable $dateFrom, \DateTimeImmutable $dateTo): array
    {
        if ([] === $employeeIds) {
            return [];
        }

        return $this->createQueryBuilder('te')
            ->innerJoin('te.employee', 'e')
            ->addSelect('e')
            ->andWhere('e.id IN (:employeeIds)')
            ->andWhere('te.workDate BETWEEN :dateFrom AND :dateTo')
            ->setParameter('employeeIds', $employeeIds)
            ->setParameter('dateFrom', $dateFrom->setTime(0, 0))
            ->setParameter('dateTo', $dateTo->setTime(0, 0))
            ->orderBy('te.workDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
