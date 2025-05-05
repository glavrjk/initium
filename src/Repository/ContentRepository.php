<?php

namespace App\Repository;

use App\Entity\Content;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Content>
 */
class ContentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Content::class);
    }

    /**
     * @return Content[] Returns an array of Content objects
     */
    public function findContentsBy(?string $title = null, ?string $description = null): array
    {
        $query = $this->createQueryBuilder('c');
        if ($title) {
            $query
                ->andWhere('c.title LIKE :title')
                ->setParameter('title', '%' . $title . '%');
        }
        if ($description) {
            $query
                ->orWhere('c.description LIKE :description')
                ->setParameter('description', '%' . $description . '%');
        }
        return $query
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
