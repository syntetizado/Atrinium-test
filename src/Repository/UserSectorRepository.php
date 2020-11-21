<?php

namespace App\Repository;

use App\Entity\UserSector;
use App\Entity\Sector;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserSector|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserSector|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserSector[]    findAll()
 * @method UserSector[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserSectorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSector::class);
    }

    public function countByIdUser($value): ?int
    {

        return $this->createQueryBuilder('s')
            ->select('count(s.id)')
            ->andWhere('s.idUser = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getSingleScalarResult();
        ;
    }

    public function findSectorsByIdUser($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.idUser = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return UserSector[] Returns an array of UserSector objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?UserSector
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
