<?php

namespace App\Repository;

use App\Entity\WarzoneTournoisEquipeResultats;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WarzoneTournoisEquipeResultats|null find($id, $lockMode = null, $lockVersion = null)
 * @method WarzoneTournoisEquipeResultats|null findOneBy(array $criteria, array $orderBy = null)
 * @method WarzoneTournoisEquipeResultats[]    findAll()
 * @method WarzoneTournoisEquipeResultats[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WarzoneTournoisEquipeResultatsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WarzoneTournoisEquipeResultats::class);
    }

    public function getAllTeamsResults($id)
    {
        return $this->createQueryBuilder('res')
            ->select('DISTINCT(e.nom) as team, res')
            ->where('e.tournois = :id')
            ->setParameter('id', $id)
            ->leftJoin('App:User', 'u1', 'WITH', 'u1.id = res.user1')
            ->leftJoin('App:User', 'u2', 'WITH', 'u2.id = res.user2')
            ->leftJoin('App:User', 'u3', 'WITH', 'u3.id = res.user3')
            ->leftJoin('App:User', 'u4', 'WITH', 'u4.id = res.user4')
            ->LeftJoin('App:WarzoneTournoisEquipe', 'e', 'WITH', 'e.id = res.equipe')
            ->LeftJoin('App:WarzoneTournois', 'tou', 'WITH', 'tou.id = res.tournois')
            ->orderBy('res.partie')
            ->getQuery()
            ->getResult();
    }

    public function getAllTeamsResultsTotal($id)
    {
        return $this->createQueryBuilder('res')
            ->select('DISTINCT(e.nom) as team, SUM(res.score) AS scoreGlobal')
            ->where('e.tournois = :id')
            ->setParameter('id', $id)
            ->leftJoin('App:User', 'u1', 'WITH', 'u1.id = res.user1')
            ->leftJoin('App:User', 'u2', 'WITH', 'u2.id = res.user2')
            ->leftJoin('App:User', 'u3', 'WITH', 'u3.id = res.user3')
            ->leftJoin('App:User', 'u4', 'WITH', 'u4.id = res.user4')
            ->LeftJoin('App:WarzoneTournoisEquipe', 'e', 'WITH', 'e.id = res.equipe')
            ->LeftJoin('App:WarzoneTournois', 'tou', 'WITH', 'tou.id = res.tournois')
            ->orderBy('scoreGlobal')
            ->groupBy('res.equipe')
            ->getQuery()
            ->getResult();
    }

    public function getAllResults($id)
    {
        return $this->createQueryBuilder('res')
            ->where('e.tournois = :id')
            ->setParameter('id', $id)
            ->leftJoin('App:User', 'u1', 'WITH', 'u1.id = res.user1')
            ->leftJoin('App:User', 'u2', 'WITH', 'u2.id = res.user2')
            ->leftJoin('App:User', 'u3', 'WITH', 'u3.id = res.user3')
            ->leftJoin('App:User', 'u4', 'WITH', 'u4.id = res.user4')
            ->LeftJoin('App:WarzoneTournoisEquipe', 'e', 'WITH', 'e.id = res.equipe')
            ->LeftJoin('App:WarzoneTournois', 'tou', 'WITH', 'tou.id = res.tournois')
            ->orderBy('e.nom')
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return WarzoneTournoisEquipe[] Returns an array of WarzoneTournoisEquipe objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?WarzoneTournoisEquipe
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
