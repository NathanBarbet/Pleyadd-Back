<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\Query;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getClassementWarzoneGlobal($dateDebut, $dateFin)
    {
        return $this->createQueryBuilder('u')
            ->select('u.id,u.avatar,u.warzoneKills,u.warzoneWins,u.warzoneKdratio,u.warzoneGamesplayed,u.pseudo,SUM(up.point) as pointsGlobal')
            ->Where(':dateDebut <= up.date_give AND :dateFin >= up.date_give')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->leftJoin('App:WarzoneUserPoint', 'up', 'WITH', 'u.id = up.user_id')
            ->groupBy('u.id')
            ->orderBy('pointsGlobal', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getClassementWarzoneMensuel($date)
    {
        return $this->createQueryBuilder('u')
            ->select('u.id,u.avatar,u.warzoneKills,u.warzoneWins,u.warzoneKdratio,u.warzoneGamesplayed,u.pseudo,SUM(up.point) as pointsMensuel')
            ->Where('MONTH(up.date_give) = :now')
            ->setParameter('now', $date)
            ->leftJoin('App:WarzoneUserPoint', 'up', 'WITH', 'u.id = up.user_id')
            ->groupBy('u.id')
            ->orderBy('pointsMensuel', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getPseudoPointsGlobal($userPseudo, $dateDebut, $dateFin)
    {
        return $this->createQueryBuilder('u')
            ->select('SUM(up.point) as pointsGlobal')
            ->Where('u.pseudo = :pseudo')
            ->andWhere(':dateDebut <= up.date_give AND :dateFin >= up.date_give')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->setParameter('pseudo', $userPseudo)
            ->leftJoin('App:WarzoneUserPoint', 'up', 'WITH', 'u.id = up.user_id')
            ->groupBy('u.id')
            ->orderBy('pointsGlobal', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getPseudoPointsGlobalProfil($userPseudo, $dateDebut, $dateFin)
    {
        return $this->createQueryBuilder('u')
            ->select('SUM(up.point)')
            ->Where('u.pseudo = :pseudo')
            ->andWhere(':dateDebut <= up.date_give AND :dateFin >= up.date_give')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->setParameter('pseudo', $userPseudo)
            ->leftJoin('App:WarzoneUserPoint', 'up', 'WITH', 'u.id = up.user_id')
            ->groupBy('u.id')
            ->getQuery()
            ->getResult();
    }

    public function getPseudoPointsMensuel($date, $userPseudo)
    {
        return $this->createQueryBuilder('u')
            ->select('SUM(up.point) as pointsMensuel')
            ->Where('MONTH(up.date_give) = :now')
            ->andWhere('u.pseudo = :userPseudo')
            ->setParameter('now', $date)
            ->setParameter('userPseudo', $userPseudo)
            ->leftJoin('App:WarzoneUserPoint', 'up', 'WITH', 'u.id = up.user_id')
            ->groupBy('u.id')
            ->orderBy('pointsMensuel', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getInscritJour($jour)
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id) as count')
            ->Where('DAYOFYEAR(u.dateRegister) = :now')
            ->setParameter('now', $jour)
            ->getQuery()
            ->getResult();
    }

    public function getInscritSemaine($semaine)
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id) as count')
            ->Where('WEEK(u.dateRegister, 1) = :now')
            ->setParameter('now', $semaine)
            ->getQuery()
            ->getResult();
    }

    public function getInscritMois($mois)
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id) as count')
            ->Where('MONTH(u.dateRegister) = :now')
            ->setParameter('now', $mois)
            ->getQuery()
            ->getResult();
    }

    public function getInscritAn($an)
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id) as count')
            ->Where('YEAR(u.dateRegister) = :now')
            ->setParameter('now', $an)
            ->getQuery()
            ->getResult();
    }

    public function getInscritTotal()
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id) as count')
            ->getQuery()
            ->getResult();
    }

    public function getPseudoForApiStats()
    {
        return $this->createQueryBuilder('u')
            ->select('u.pseudo, u.trn, u.warzonePlateforme, u.warzoneWins, u.warzoneKills, u.warzoneKdratio, u.warzoneGamesplayed')
            ->where('u.trn IS NOT NULL AND u.warzonePlateforme IS NOT NULL')
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findEntitiesByString($str)
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT e
                FROM App:User e
                WHERE e.pseudo LIKE :str'
            )
            ->setParameter('str', '%' . $str . '%')
            ->getResult();
    }

    public function getInscritParJours($jour, $jour30)
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id) as count, DAYNAME(u.dateRegister) as jourSemaine, DAY(u.dateRegister) as numJour, DAYOFYEAR(u.dateRegister) as numJourYear')
            ->Where('DAYOFYEAR(u.dateRegister) <= :now AND DAYOFYEAR(u.dateRegister) >= :jour30')
            ->setParameter('now', $jour)
            ->setParameter('jour30', $jour30)
            ->orderBy('numJourYear', 'ASC')
            ->groupBy('numJourYear')
            ->getQuery()
            ->getResult();
    }

    public function getInscritParMois()
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id) as count, MONTHNAME(u.dateRegister) as mois, MONTH(u.dateRegister) as moisnbr, YEAR(u.dateRegister) as year')
            ->orderBy('year', 'ASC')
            ->orderBy('moisnbr', 'ASC')
            ->groupBy('year', 'moisnbr')
            ->getQuery()
            ->getResult();
    }

    public function getInscritParSemaine()
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id) as count, WEEK(u.dateRegister) as weeknbr, YEAR(u.dateRegister) as year')
            ->orderBy('year', 'ASC')
            ->orderBy('weeknbr', 'ASC')
            ->groupBy('year', 'weeknbr')
            ->getQuery()
            ->getResult();
    }

    public function getInscritWithTrn()
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id) as count')
            ->where('u.trn IS NOT NULL')
            ->getQuery()
            ->getResult();
    }

    public function getInscritWithoutTrn()
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id) as count')
            ->where('u.trn IS NULL')
            ->getQuery()
            ->getResult();
    }

    public function kdRatioMoy()
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id) as count, SUM(u.warzoneKdratio)')
            ->Where('u.warzoneKdratio IS NOT NULL AND u.warzoneKdratio != 0')
            ->getQuery()
            ->getResult();
    }
}
