<?php

declare(strict_types=1);

namespace App\Tests\Unit\Cart;

use App\DataFixtures\CartFixture;
use App\Entity\Cart\CartItem;
use App\Service\Cart\CartManagerInterface;
use App\Service\Cart\Exception\CartItemNotFoundException;
use App\Service\Cart\Exception\ItemByUserExistsException;
use App\Service\Cart\Exception\ItemsAmountLimitReachedException;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CartManagerTest extends KernelTestCase
{
    private CartManagerInterface $cartManager;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $entityManager = $container->get(EntityManagerInterface::class);
        assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;

        $cartManager = $container->get(CartManagerInterface::class);
        assert($cartManager instanceof CartManagerInterface);
        $this->cartManager = $cartManager;

        $executor = new ORMExecutor($this->entityManager, new ORMPurger());
        $executor->execute([new CartFixture()]);
    }

    // --- saveCartItem() ---

    public function testSaveCartItemSuccessfully(): void
    {
        // userId=10 / itemId=5 do not exist in the fixture → save must succeed
        $cartItemDTO = $this->cartManager->saveCartItem(userId: 10, itemId: 5, count: 2);

        $this->assertSame(10, $cartItemDTO->userId);
        $this->assertSame(5, $cartItemDTO->itemId);
        $this->assertSame(2, $cartItemDTO->count);
    }

    public function testSaveCartItemWhenItemIdExists(): void
    {
        // CartFixture already has userId=2 / itemId=1
        $this->expectException(ItemByUserExistsException::class);

        $this->cartManager->saveCartItem(userId: 2, itemId: 1, count: 5);
    }

    public function testSaveCartItemsLimitReached(): void
    {
        // services_test.yaml sets cart.total_amount=3; CartFixture gives userId=2 two items already.
        // Adding a third item brings the count to the limit; a fourth must throw.
        $this->cartManager->saveCartItem(userId: 2, itemId: 5, count: 1);

        $this->expectException(ItemsAmountLimitReachedException::class);

        $this->cartManager->saveCartItem(userId: 2, itemId: 6, count: 1);
    }

    // --- removeCartItem() ---

    public function testRemoveCartItemSuccessfully(): void
    {
        // CartFixture: userId=1 / itemId=3 / count=1
        $existingItem = $this->entityManager
            ->getRepository(CartItem::class)
            ->findOneBy(['itemId' => 3, 'userId' => 1]);

        $result = $this->cartManager->removeCartItem($existingItem->getId());

        $this->assertTrue($result);
    }

    public function testRemoveCartItemWhenCartItemDoesntExist(): void
    {
        $result = $this->cartManager->removeCartItem(9999);

        $this->assertFalse($result);
    }

    // --- changeCartItem() ---

    public function testChangeCartItemSuccessfully(): void
    {
        // CartFixture: userId=2 / itemId=1 / count=3  → change count to 10
        $existingItem = $this->entityManager
            ->getRepository(CartItem::class)
            ->findOneBy(['itemId' => 1, 'userId' => 2]);

        $cartItemDTO = $this->cartManager->changeCartItem($existingItem->getId(), 10);

        $this->assertSame($existingItem->getId(), $cartItemDTO->id);
        $this->assertSame(1, $cartItemDTO->itemId);
        $this->assertSame(10, $cartItemDTO->count);
        $this->assertSame(2, $cartItemDTO->userId);
    }

    public function testChangeCartItemWhenCartItemDoesntExist(): void
    {
        $this->expectException(CartItemNotFoundException::class);

        $this->cartManager->changeCartItem(9999, 5);
    }

    // --- showCart() ---

    public function testShowCartWhenItHasItems(): void
    {
        // CartFixture: userId=2 has two items (itemId=1 count=3, itemId=2 count=1)
        $cartDTO = $this->cartManager->showCartItems(2);

        $this->assertNotNull($cartDTO);
        $this->assertSame(2, $cartDTO->userId);
        $this->assertCount(2, $cartDTO->items);
    }

    public function testShowCartWhenNoItems(): void
    {
        $cartDTO = $this->cartManager->showCartItems(999);

        $this->assertNull($cartDTO);
    }
}
