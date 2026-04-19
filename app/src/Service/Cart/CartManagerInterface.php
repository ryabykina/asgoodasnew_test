<?php

declare(strict_types=1);

namespace App\Service\Cart;

use App\DTO\Cart\CartDTO;
use App\DTO\Cart\CartItemDTO;

interface CartManagerInterface
{
    public function saveCartItem(int $userId, int $itemId, int $count): CartDTO;

    public function removeCartItem(int $cartItemId): bool;

    public function changeCartItem(int $cartItemId, int $count): CartItemDTO;

    public function showCartItems(int $userId): ?CartDTO;
}
