<?php

namespace App\Repository;

use App\Entity\WarzoneTournoisTokenEquipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WarzoneTournoisTokenEquipe|null find($id, $lockMode = null, $lockVersion = null)
 * @method WarzoneTournoisTokenEquipe|null findOneBy(array $criteria, array $orderBy = null)
 * @method WarzoneTournoisTokenEquipe[]    findAll()
 * @method WarzoneTournoisTokenEquipe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WarzoneTournoisTokenEquipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WarzoneTournoisTokenEquipe::class);
    }


    public function getAlreadyTokenValideUserInTournois($pseudo, $id)
    {
        return $this->createQueryBuilder('tok')
            ->select('u.pseudo, tou.id, tok.token, tok.isUse')
            ->where('e.tournois = :id')
            ->andWhere('u.pseudo = :pseudo')
            ->andWhere('tok.isUse = 1')
            ->setParameter('id', $id)
            ->setParameter('pseudo', $pseudo)
            ->leftJoin('App:User', 'u', 'WITH', 'u.id = tok.userReceive')
            ->LeftJoin('App:WarzoneTournoisEquipe', 'e', 'WITH', 'e.id = tok.equipe')
            ->LeftJoin('App:WarzoneTournois', 'tou', 'WITH', 'tou.id = tok.tournois')
            ->orderBy('u.pseudo')
            ->getQuery()
            ->getResult();
    }

    public function getCountSendToken($pseudo, $id)
    {
        return $this->createQueryBuilder('tok')
            ->select('COUNT(tok.id)')
            ->where('e.tournois = :id')
            ->andWhere('u.pseudo = :pseudo')
            ->setParameter('id', $id)
            ->setParameter('pseudo', $pseudo)
            ->leftJoin('App:User', 'u', 'WITH', 'u.id = tok.userSend')
            ->LeftJoin('App:WarzoneTournoisEquipe', 'e', 'WITH', 'e.id = tok.equipe')
            ->LeftJoin('App:WarzoneTournois', 'tou', 'WITH', 'tou.id = tok.tournois')
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
