<?php

declare(strict_types=1);

namespace App\Repository\Cart;

use App\Entity\Cart\CartItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CartItem>
 */
class CartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartItem::class);
    }

    public function findByItemIdAndUserId(int $itemId, int $userId): ?CartItem
    {
        return $this->findOneBy(['itemId' => $itemId, 'userId' => $userId]);
    }

    /**
     * @return CartItem[]
     */
    public function findByUserId(int $userId): array
    {
        return $this->findBy(['userId' => $userId]);
    }

    public function findOneId(int $id): ?CartItem
    {
        return $this->find($id);
    }

    public function remove(CartItem $cart): void
    {
        $this->getEntityManager()->remove($cart);
        $this->getEntityManager()->flush();
    }

    public function save(CartItem $cart): void
    {
        $this->getEntityManager()->persist($cart);
        $this->getEntityManager()->flush();
    }

    public function updateCount(CartItem $cart, int $count): void
    {
        $cart->setCount($count);
        $this->getEntityManager()->flush();
    }

    public function getTotalAmountByUser(int $userId): int
    {
        return (int) $this->createQueryBuilder('ci')
            ->select('COUNT(ci.id)')
            ->where('ci.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
