<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Cart\CartItem;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CartFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $cartItem = new CartItem();
        $cartItem->setItemId(1);
        $cartItem->setUserId(2);
        $cartItem->setCount(3);

        $manager->persist($cartItem);

        $cartItem2 = new CartItem();
        $cartItem2->setItemId(2);
        $cartItem2->setUserId(2);
        $cartItem2->setCount(1);

        $manager->persist($cartItem2);

        $cartItem3 = new CartItem();
        $cartItem3->setItemId(3);
        $cartItem3->setUserId(1);
        $cartItem3->setCount(1);

        $manager->persist($cartItem3);

        $manager->flush();
    }
}
