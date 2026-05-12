<?php

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * Find all reviews for a specific product, ordered by newest first.
     */
    public function findByProduct(int $productId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.product = :productId')
            ->setParameter('productId', $productId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calculate the average rating for a product.
     */
    public function getAverageRating(int $productId): ?float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) as avgRating')
            ->andWhere('r.product = :productId')
            ->setParameter('productId', $productId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : null;
    }
}
