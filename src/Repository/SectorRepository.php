<?php

namespace App\Repository;

use App\Entity\Sector;
use App\Entity\UserSector;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Sector|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sector|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sector[]    findAll()
 * @method Sector[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SectorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sector::class);
    }

    public function countAll(): ?int
    {
        return $this->createQueryBuilder('s')
            ->select('count(s.id)')
            ->getQuery()
            ->getSingleScalarResult();
        ;
    }

    public function countByIdUser($value): ?int
    {

        return $this->createQueryBuilder('s')
            ->select('count(s.id)')
            ->andWhere('s.id_user = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getSingleScalarResult();
        ;
    }

    public function findAllByUserSectorIdArray($array){
        $items_array = [];

        foreach ($array as $sector) {
            $item = $this->createQueryBuilder('s')
                ->andWhere('s.id = :val')
                ->setParameter('val', $sector->getIdSector())
                ->getQuery()
                ->getOneOrNullResult();

            $items_array[] = $item;
        }

        return $items_array;
    }

    public function findOneByUsername($value): ?Sector
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.name = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    // /**
    //  * @return Sector[] Returns an array of Sector objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Sector
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
