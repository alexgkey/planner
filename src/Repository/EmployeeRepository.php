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
    public const SORT_FIELD_FIO = 'fio';
    public const SORT_FIELD_DEPARTMENT = 'department';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Employee::class);
    }

    /**
     * @return Employee[]
     */
    public function findActive(): array
    {
        return $this->findActiveForListing();
    }

    /**
     * @return Employee[]
     */
    public function findActiveByDepartment(?Department $department): array
    {
        if (null !== $department) {
            return $this->findActiveForListing($department);
        }

        return [];
    }

    /**
     * @return Employee[]
     */
    public function findActiveForListing(
        ?Department $scopeDepartment = null,
        ?string $fioFilter = null,
        ?int $departmentIdFilter = null,
        string $sortField = self::SORT_FIELD_FIO,
        string $sortDirection = 'ASC'
    ): array {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.department', 'd')
            ->addSelect('d')
            ->andWhere('e.isActive = :active')
            ->setParameter('active', true);

        if (null !== $scopeDepartment) {
            $qb
                ->andWhere('e.department = :scopeDepartment')
                ->setParameter('scopeDepartment', $scopeDepartment);
        }

        if (null !== $fioFilter && '' !== $fioFilter) {
            $qb
                ->andWhere('LOWER(e.fio) LIKE LOWER(:fioFilter)')
                ->setParameter('fioFilter', '%'.$fioFilter.'%');
        }

        if (null !== $departmentIdFilter) {
            $qb
                ->andWhere('d.id = :departmentIdFilter')
                ->setParameter('departmentIdFilter', $departmentIdFilter);
        }

        $sortField = match ($sortField) {
            self::SORT_FIELD_DEPARTMENT => self::SORT_FIELD_DEPARTMENT,
            default => self::SORT_FIELD_FIO,
        };
        $sortDirection = 'DESC' === strtoupper($sortDirection) ? 'DESC' : 'ASC';

        if (self::SORT_FIELD_DEPARTMENT === $sortField) {
            $qb
                ->addOrderBy('CASE WHEN d.title IS NULL THEN 1 ELSE 0 END', 'ASC')
                ->addOrderBy('d.title', $sortDirection)
                ->addOrderBy('e.fio', 'ASC');
        } else {
            $qb
                ->addOrderBy('e.fio', $sortDirection)
                ->addOrderBy('d.title', 'ASC');
        }

        return $qb->getQuery()->getResult();
    }
}
