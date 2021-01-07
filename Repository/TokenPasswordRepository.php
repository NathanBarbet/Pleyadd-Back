<?php

namespace App\Repository;

use App\Entity\TokenPassword;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TokenPassword|null find($id, $lockMode = null, $lockVersion = null)
 * @method TokenPassword|null findOneBy(array $criteria, array $orderBy = null)
 * @method TokenPassword[]    findAll()
 * @method TokenPassword[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TokenPasswordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TokenPassword::class);
    }
}
