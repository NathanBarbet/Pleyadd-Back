<?php

namespace App\Repository;

use App\Entity\WarzoneUserPalmares;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WarzoneUserPalmares|null find($id, $lockMode = null, $lockVersion = null)
 * @method WarzoneUserPalmares|null findOneBy(array $criteria, array $orderBy = null)
 * @method WarzoneUserPalmares[]    findAll()
 * @method WarzoneUserPalmares[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WarzoneUserPalmaresRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WarzoneUserPalmares::class);
    }

    public function getTrophes($user)
    {
        return $this->createQueryBuilder('up')
            ->select('wt.id, wt.type as type , up.position')
            ->Where('up.user_id = :user')
            ->setParameter('user', $user)
            ->leftJoin('App:WarzoneTournois', 'wt', 'WITH', 'up.tournois_id = wt.id')
            ->getQuery()
            ->getResult();
    }

    public function getTrophesPseudo($pseudo)
    {
        return $this->createQueryBuilder('up')
            ->select('wt.id, wt.type as type , up.position')
            ->Where('up.user_id = :pseudo')
            ->setParameter('pseudo', $pseudo)
            ->leftJoin('App:WarzoneTournois', 'wt', 'WITH', 'up.tournois_id = wt.id')
            ->getQuery()
            ->getResult();
    }

    public function getTop($user)
    {
        return $this->createQueryBuilder('up')
            ->select('wt.type as type ,SUM(up.partTop1) as top1, SUM(up.partTop3) as top3, SUM(up.partTop10) as top10, SUM(up.partTop15) as top15, SUM(up.partTop20) as top20')
            ->Where('up.user_id = :user')
            ->setParameter('user', $user)
            ->leftJoin('App:WarzoneTournois', 'wt', 'WITH', 'up.tournois_id = wt.id')
            ->groupBy('wt.type')
            ->getQuery()
            ->getResult();
    }

    public function getTopPseudo($pseudo)
    {
        return $this->createQueryBuilder('up')
            ->select('wt.type as type ,SUM(up.partTop1) as top1, SUM(up.partTop3) as top3, SUM(up.partTop10) as top10, SUM(up.partTop15) as top15, SUM(up.partTop20) as top20')
            ->Where('up.user_id = :pseudo')
            ->setParameter('pseudo', $pseudo)
            ->leftJoin('App:WarzoneTournois', 'wt', 'WITH', 'up.tournois_id = wt.id')
            ->groupBy('wt.type')
            ->getQuery()
            ->getResult();
    }

    public function getPalmaresSaison($user, $dateDebut, $dateFin)
    {
        return $this->createQueryBuilder('up')
            ->select('up.position, up.nombreKills')
            ->Where('up.user_id = :user')
            ->andWhere(':dateDebut <= wt.dateDebut AND :dateFin >= wt.dateDebut')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->setParameter('user', $user)
            ->leftJoin('App:WarzoneTournois', 'wt', 'WITH', 'up.tournois_id = wt.id')
            ->getQuery()
            ->getResult();
    }

    public function getPalmaresSaisonPseudo($pseudo, $dateDebut, $dateFin)
    {
        return $this->createQueryBuilder('up')
            ->select('up.position, up.nombreKills')
            ->Where('up.user_id = :user')
            ->andWhere(':dateDebut <= wt.dateDebut AND :dateFin >= wt.dateDebut')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->setParameter('user', $pseudo)
            ->leftJoin('App:WarzoneTournois', 'wt', 'WITH', 'up.tournois_id = wt.id')
            ->getQuery()
            ->getResult();
    }

    /*
    public function findOneBySomeField($value): ?UserPoint
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
