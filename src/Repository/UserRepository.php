<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Находит пользователей, которых может видеть текущий пользователь.
     */
    public function findVisibleFor(UserInterface $currentUser): array
    {
        $qb = $this->createQueryBuilder('u');

        // Администратор видит всех
        if (in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            // Никаких ограничений
        }
        // Менеджер видит Директоров и обычных пользователей
        elseif (in_array('ROLE_MANAGER', $currentUser->getRoles())) {
            $qb->andWhere('NOT u.roles LIKE :role_admin')
               ->andWhere('NOT u.roles LIKE :role_manager')
               ->setParameter('role_admin', '%"ROLE_ADMIN"%')
               ->setParameter('role_manager', '%"ROLE_MANAGER"%');
        }
        // Директор видит только обычных пользователей
        elseif (in_array('ROLE_DIR', $currentUser->getRoles())) {
            $qb->andWhere('u.roles = :empty_roles')
               ->setParameter('empty_roles', '[]');
        }
        // Обычный пользователь не видит никого
        else {
            $qb->andWhere('1 = 0'); // Не возвращать ничего
        }

        // Всегда исключаем самого себя из списка
        $qb->andWhere('u.id != :current_user_id')
           ->setParameter('current_user_id', $currentUser->getId());
        // Исключаем неактивных
        $qb->andWhere('u.isActive != false');

        return $qb->getQuery()->getResult();
    }
}
