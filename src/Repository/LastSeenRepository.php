<?php

namespace App\Repository;

use App\Entity\LastSeen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LastSeen>
 *
 * @method LastSeen|null find($id, $lockMode = null, $lockVersion = null)
 * @method LastSeen|null findOneBy(array $criteria, array $orderBy = null)
 * @method LastSeen[]    findAll()
 * @method LastSeen[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LastSeenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LastSeen::class);
    }
}
