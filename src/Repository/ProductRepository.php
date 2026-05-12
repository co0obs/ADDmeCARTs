<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Search products by keyword, filter by category/rating, and sort by price.
     */
    public function searchAndFilter(?string $keyword, ?string $category, ?int $minStars, ?string $sort): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($keyword) {
            $qb->andWhere('p.name LIKE :keyword OR p.description LIKE :keyword')
               ->setParameter('keyword', '%' . $keyword . '%');
        }

        if ($category && $category !== 'all') {
            $qb->andWhere('p.category = :category')
               ->setParameter('category', $category);
        }

        if ($minStars && $minStars > 0) {
            $qb->andWhere('p.starRating >= :minStars')
               ->setParameter('minStars', (float) $minStars);
        }

        if ($sort === 'price_asc') {
            $qb->orderBy('COALESCE(p.salePrice, p.price)', 'ASC');
        } elseif ($sort === 'price_desc') {
            $qb->orderBy('COALESCE(p.salePrice, p.price)', 'DESC');
        } else {
            $qb->orderBy('p.id', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Product[] Returns an array of Product objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Product
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
