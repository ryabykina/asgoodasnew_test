<?php

declare(strict_types=1);

namespace App\Service\Cart;

use App\DTO\Cart\CartDTO;
use App\DTO\Cart\CartItemDTO;
use App\Entity\Cart\CartItem;
use App\Repository\Cart\CartRepository;
use App\Service\Cart\Exception\CartItemNotFoundException;
use App\Service\Cart\Exception\ItemByUserExistsException;

class CartManager implements CartManagerInterface
{
    public function __construct(private CartRepository $cartRepository)
    {

    }

    public function saveCartItem(int $userId, int $itemId, int $count): CartDTO
    {
        if (null !== $this->cartRepository->findByItemIdAndUserId($itemId, $userId)) {
            throw new ItemByUserExistsException(sprintf('The itemId = %d for the userId = %d exists', $itemId, $userId));
        }

        $cart = new CartItem()
            ->setUserId($userId)
            ->setItemId($itemId)
            ->setCount($count);

        $this->cartRepository->save($cart);

        $cartDTO = new CartDTO();
        $cartDTO->userId = $cart->getUserId();
        $cartItemDTO = new CartItemDTO();
        $cartItemDTO->id = $cart->getId();
        $cartItemDTO->itemId = $cart->getItemId();
        $cartItemDTO->count = $cart->getCount();
        $cartDTO->items[] = $cartItemDTO;

        return $cartDTO;
    }

    public function removeCartItem(int $cartItemId): bool
    {
        if (null === ($cart = $this->cartRepository->findOneId($cartItemId))) {
            return false;
        }

        $this->cartRepository->remove($cart);

        return true;
    }

    /**
     * @throws CartItemNotFoundException
     */
    public function changeCartItem(int $cartItemId, int $count): CartItemDTO
    {
        if (null === ($cart = $this->cartRepository->findOneId($cartItemId))) {
            throw new CartItemNotFoundException(sprintf('The cartItemId = %d does not exist', $cartItemId));
        }

        $this->cartRepository->updateCount($cart, $count);

        $cartItemDTO = new CartItemDTO();
        $cartItemDTO->id = $cart->getId();
        $cartItemDTO->itemId = $cart->getItemId();
        $cartItemDTO->count = $cart->getCount();

        return $cartItemDTO;
    }

    public function showCartItems(int $userId): ?CartDTO
    {
        $items = $this->cartRepository->findByUserId($userId);

        if (0 === count($items)) {
            return null;
        }

        $cartDTO = new CartDTO();
        $cartDTO->userId = $items[0]->getUserId();

        foreach ($items as $item) {
            $cartItemDTO = new CartItemDTO();
            $cartItemDTO->id = $item->getId();
            $cartItemDTO->itemId = $item->getItemId();
            $cartItemDTO->count = $item->getCount();
            $cartDTO->items[] = $cartItemDTO;
        }

        return $cartDTO;
    }
}
