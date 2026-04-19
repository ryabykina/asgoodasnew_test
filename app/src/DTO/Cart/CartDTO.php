<?php

declare(strict_types=1);

namespace App\DTO\Cart;

class CartDTO
{
    public int $userId;

    /**
     * @var CartItemDTO[]
     */
    public array $items;
}
