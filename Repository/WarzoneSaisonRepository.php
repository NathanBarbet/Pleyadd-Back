<?php

namespace App\Repository;

use App\Entity\WarzoneSaison;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WarzoneSaison|null find($id, $lockMode = null, $lockVersion = null)
 * @method WarzoneSaison|null findOneBy(array $criteria, array $orderBy = null)
 * @method WarzoneSaison[]    findAll()
 * @method WarzoneSaison[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WarzoneSaisonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WarzoneSaison::class);
    }

    public function getActualSaison($dateNow)
    {
        return $this->createQueryBuilder('s')
            ->Where('TIMESTAMP(s.dateDebut) <= :dateNow AND TIMESTAMP(s.dateFin) >= :dateNow')
            ->setParameter('dateNow', $dateNow)
            ->getQuery()
            ->getResult();
    }

    public function findAllSaisons()
    {
        return $this->createQueryBuilder('s')
            ->select('s.nom, s.dateDebut, s.dateFin')
            ->getQuery()
            ->getResult();
    }

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
