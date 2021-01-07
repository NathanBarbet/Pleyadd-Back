<?php

namespace App\Repository;

use App\Entity\WarzoneUserPoint;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WarzoneUserPoint|null find($id, $lockMode = null, $lockVersion = null)
 * @method WarzoneUserPoint|null findOneBy(array $criteria, array $orderBy = null)
 * @method WarzoneUserPoint[]    findAll()
 * @method WarzoneUserPoint[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WarzoneUserPointRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WarzoneUserPoint::class);
    }

    public function pointsWarzoneGlobal($pseudo)
    {
        return $this->createQueryBuilder('upg')
            ->Where('upg.user_id = :pseudo')
            ->setParameter('pseudo', $pseudo)
            ->select('SUM(upg.point) as pointsGlobal')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function pointsWarzoneMensuel($pseudo, $date)
    {
        return $this->createQueryBuilder('upg')
            ->Where('upg.user_id = :pseudo')
            ->andWhere('MONTH(upg.date_give) = :now')
            ->setParameter('pseudo', $pseudo)
            ->setParameter('now', $date)
            ->select('SUM(upg.point) as pointsMensuel')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getInscritTotalWithPoints()
    {
        return $this->createQueryBuilder('up')
            ->select('COUNT(up.user_id)')
            ->groupBy('up.user_id')
            ->getQuery()
            ->getResult();
    }

    public function getInscritSaisonWithPoints($dateDebut, $dateFin)
    {
        return $this->createQueryBuilder('up')
            ->select('COUNT(up.user_id)')
            ->where(':dateDebut <= up.date_give AND :dateFin >= up.date_give')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->groupBy('up.user_id')
            ->getQuery()
            ->getResult();
    }

    public function getPointsGiveTotalSaisons($dateDebut, $dateFin)
    {
        return $this->createQueryBuilder('up')
            ->select('SUM(up.point) as count')
            ->where(':dateDebut <= up.date_give AND :dateFin >= up.date_give')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->getQuery()
            ->getOneOrNullResult();
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
