<?php

namespace App\Repository;

use App\Entity\WarzoneTournoisEquipeList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WarzoneTournoisEquipeList|null find($id, $lockMode = null, $lockVersion = null)
 * @method WarzoneTournoisEquipeList|null findOneBy(array $criteria, array $orderBy = null)
 * @method WarzoneTournoisEquipeList[]    findAll()
 * @method WarzoneTournoisEquipeList[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WarzoneTournoisEquipeListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WarzoneTournoisEquipeList::class);
    }

    public function getWarzoneTournoisEquipe($id)
    {
        return $this->createQueryBuilder('el')
            ->select('e.nom,e.elo,el.lead,u.avatar,u.pseudo,el.isValide')
            ->where('e.tournois = :id')
            ->setParameter('id', $id)
            ->leftJoin('App:User', 'u', 'WITH', 'u.id = el.user')
            ->LeftJoin('App:WarzoneTournoisEquipe', 'e', 'WITH', 'e.id = el.equipe')
            ->LeftJoin('App:WarzoneTournois', 't', 'WITH', 't.id = e.tournois')
            ->orderBy('e.nom')
            ->getQuery()
            ->getResult();
    }

    public function getUserValideInWarzoneTournois($pseudo, $id)
    {
        return $this->createQueryBuilder('el')
            ->select('u.pseudo, t.id, e.nom, el.lead, el.isValide')
            ->where('e.tournois = :id')
            ->andWhere('u.pseudo = :pseudo')
            ->andWhere('el.isValide = 1')
            ->setParameter('id', $id)
            ->setParameter('pseudo', $pseudo)
            ->leftJoin('App:User', 'u', 'WITH', 'u.id = el.user')
            ->LeftJoin('App:WarzoneTournoisEquipe', 'e', 'WITH', 'e.id = el.equipe')
            ->LeftJoin('App:WarzoneTournois', 't', 'WITH', 't.id = e.tournois')
            ->orderBy('e.nom')
            ->getQuery()
            ->getResult();
    }

    public function getUserInWarzoneTournois($pseudo, $id)
    {
        return $this->createQueryBuilder('el')
            ->select('u.pseudo, t.id, e.nom, el.lead, el.isValide')
            ->where('e.tournois = :id')
            ->andWhere('u.pseudo = :pseudo')
            ->setParameter('id', $id)
            ->setParameter('pseudo', $pseudo)
            ->leftJoin('App:User', 'u', 'WITH', 'u.id = el.user')
            ->LeftJoin('App:WarzoneTournoisEquipe', 'e', 'WITH', 'e.id = el.equipe')
            ->LeftJoin('App:WarzoneTournois', 't', 'WITH', 't.id = e.tournois')
            ->orderBy('e.nom')
            ->getQuery()
            ->getResult();
    }

    public function getAllUserInWarzoneTeam($nom, $id)
    {
        return $this->createQueryBuilder('el')
            ->where('e.tournois = :id')
            ->andWhere('e.nom = :nom')
            ->setParameter('id', $id)
            ->setParameter('nom', $nom)
            ->leftJoin('App:User', 'u', 'WITH', 'u.id = el.user')
            ->LeftJoin('App:WarzoneTournoisEquipe', 'e', 'WITH', 'e.id = el.equipe')
            ->LeftJoin('App:WarzoneTournois', 't', 'WITH', 't.id = e.tournois')
            ->orderBy('e.nom')
            ->getQuery()
            ->getResult();
    }

    public function getLead($idTeam)
    {
        return $this->createQueryBuilder('el')
            ->select('u.pseudo, el.lead')
            ->where('el.equipe = :id')
            ->andWhere('el.lead = 1')
            ->setParameter('id', $idTeam)
            ->leftJoin('App:User', 'u', 'WITH', 'u.id = el.user')
            ->LeftJoin('App:WarzoneTournoisEquipe', 'e', 'WITH', 'e.id = el.equipe')
            ->LeftJoin('App:WarzoneTournois', 't', 'WITH', 't.id = e.tournois')
            ->getQuery()
            ->getResult();
    }

    public function getAllUnvalide($id)
    {
        return $this->createQueryBuilder('el')
            ->select('e.id')
            ->where('e.tournois = :id')
            ->andWhere('el.isValide = 0')
            ->setParameter('id', $id)
            ->leftJoin('App:User', 'u', 'WITH', 'u.id = el.user')
            ->LeftJoin('App:WarzoneTournoisEquipe', 'e', 'WITH', 'e.id = el.equipe')
            ->LeftJoin('App:WarzoneTournois', 't', 'WITH', 't.id = e.tournois')
            ->orderBy('e.id')
            ->groupBy('e.id')
            ->getQuery()
            ->getResult();
    }

    public function playersOnTournois()
    {
        return $this->createQueryBuilder('el')
            ->select('t.nom, COUNT(el.id)')
            ->leftJoin('App:User', 'u', 'WITH', 'u.id = el.user')
            ->LeftJoin('App:WarzoneTournoisEquipe', 'e', 'WITH', 'e.id = el.equipe')
            ->LeftJoin('App:WarzoneTournois', 't', 'WITH', 't.id = e.tournois')
            ->groupBy('t.nom')
            ->orderBy('t.dateDebut')
            ->getQuery()
            ->getResult();
    }

    public function playersOnMonth()
    {
        return $this->createQueryBuilder('el')
            ->select('MONTHNAME(t.dateDebut) as mois, COUNT(el.id), MONTH(t.dateDebut) as moisnbr')
            ->leftJoin('App:User', 'u', 'WITH', 'u.id = el.user')
            ->LeftJoin('App:WarzoneTournoisEquipe', 'e', 'WITH', 'e.id = el.equipe')
            ->LeftJoin('App:WarzoneTournois', 't', 'WITH', 't.id = e.tournois')
            ->groupBy('moisnbr')
            ->orderBy('moisnbr')
            ->getQuery()
            ->getResult();
    }

    public function playersOnSaisons($dateDebut, $dateFin)
    {
        return $this->createQueryBuilder('el')
            ->select('COUNT(el.id) as count')
            ->leftJoin('App:User', 'u', 'WITH', 'u.id = el.user')
            ->LeftJoin('App:WarzoneTournoisEquipe', 'e', 'WITH', 'e.id = el.equipe')
            ->LeftJoin('App:WarzoneTournois', 't', 'WITH', 't.id = e.tournois')
            ->where(':dateDebut <= t.dateDebut AND :dateFin >= t.dateFin')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->getQuery()
            ->getResult();
    }

    public function getWarzoneTournoisEquipeAdmin($id)
    {
        return $this->createQueryBuilder('el')
            ->select('e.id,e.nom,el.lead,u.avatar,u.pseudo,el.isValide')
            ->where('t.id = :id')
            ->setParameter('id', $id)
            ->leftJoin('App:User', 'u', 'WITH', 'u.id = el.user')
            ->LeftJoin('App:WarzoneTournoisEquipe', 'e', 'WITH', 'e.id = el.equipe')
            ->LeftJoin('App:WarzoneTournois', 't', 'WITH', 't.id = e.tournois')
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
