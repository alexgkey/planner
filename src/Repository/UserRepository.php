<?php

namespace App\Repository;

use App\Entity\Department;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public const SORT_FIELD_FIO = 'fio';
    public const SORT_FIELD_DEPARTMENT = 'department';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Находит всех пользователей, принадлежащих к определенному отделу.
     * @return User[]
     */
    public function findByDepartment(Department $department): array
    {
        return $this->findForListing($department);
    }

    /**
     * @return User[]
     */
    public function findForListing(
        ?Department $scopeDepartment = null,
        ?string $fioFilter = null,
        ?int $departmentIdFilter = null,
        string $sortField = self::SORT_FIELD_FIO,
        string $sortDirection = 'ASC'
    ): array {
        $qb = $this->createQueryBuilder('u')
            ->innerJoin('u.employee', 'e')
            ->leftJoin('e.department', 'd')
            ->addSelect('e', 'd');

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
