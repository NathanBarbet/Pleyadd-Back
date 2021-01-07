<?php

namespace App\Repository;

use App\Entity\Perm;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Perm|null find($id, $lockMode = null, $lockVersion = null)
 * @method Perm|null findOneBy(array $criteria, array $orderBy = null)
 * @method Perm[]    findAll()
 * @method Perm[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PermRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Perm::class);
    }
}
