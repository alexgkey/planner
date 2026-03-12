<?php

namespace App\Repository;

use App\Entity\Department;
use App\Entity\Employee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Employee>
 */
class EmployeeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Employee::class);
    }

    /**
     * @return Employee[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('e.fio', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Employee[]
     */
    public function findActiveByDepartment(?Department $department): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('e.fio', 'ASC');

        if (null !== $department) {
            $qb
                ->andWhere('e.department = :department')
                ->setParameter('department', $department);
        }

        return $qb->getQuery()->getResult();
    }
}