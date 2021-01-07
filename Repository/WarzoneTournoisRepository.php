<?php

namespace App\Repository;

use App\Entity\WarzoneTournois;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WarzoneTournois|null find($id, $lockMode = null, $lockVersion = null)
 * @method WarzoneTournois|null findOneBy(array $criteria, array $orderBy = null)
 * @method WarzoneTournois[]    findAll()
 * @method WarzoneTournois[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WarzoneTournoisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WarzoneTournois::class);
    }

    // /**
    //  * @return WarzoneTournois[] Returns an array of Tournois objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Tournois
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
