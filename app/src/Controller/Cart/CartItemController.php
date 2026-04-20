<?php

declare(strict_types=1);

namespace App\Controller\Cart;

use App\Service\Cart\CartManagerInterface;
use App\Service\Cart\Exception\CartItemNotFoundException;
use App\Service\Cart\Exception\ItemByUserExistsException;
use App\Service\Cart\Exception\ItemsAmountLimitReachedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CartItemController
{
    public function __construct(private CartManagerInterface $cartManager)
    {
    }

    #[Route('/cartItem', name: 'cart_item_add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $payload = $request->getPayload();

        $userId = $payload->get('userId');

        if (null === $userId) {
            return new JsonResponse(['errorMessage' => 'Ihe userId parameter is required'], Response::HTTP_BAD_REQUEST);
        }

        $itemId = $payload->get('itemId');

        if (null === $itemId) {
            return new JsonResponse(['errorMessage' => 'Ihe itemId parameter is required'], Response::HTTP_BAD_REQUEST);
        }

        $count  = $payload->get('count');

        if (null === $count) {
            return new JsonResponse(['errorMessage' => 'Ihe count parameter is required'], Response::HTTP_BAD_REQUEST);
        }

        if ($count <= 0) {
            return new JsonResponse(['errorMessage' => 'Ihe count must be more than 0'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $cartDTO = $this->cartManager->saveCartItem((int) $userId, (int) $itemId, (int) $count);

            return new JsonResponse($cartDTO, Response::HTTP_CREATED);
        } catch (ItemByUserExistsException|ItemsAmountLimitReachedException $exception) {
            return new JsonResponse(['errorMessage' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }
    }

    #[Route('/cartItem/{cartItemId}', name: 'cart_item_remove', methods: ['DELETE'])]
    public function remove(int $cartItemId): JsonResponse
    {
        if (false === $this->cartManager->removeCartItem($cartItemId)) {
            return new JsonResponse(['errorMessage' => sprintf('This cartItemId = %d doesn\'t exist', $cartItemId)], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse('', Response::HTTP_NO_CONTENT);
    }

    #[Route('/cartItem/{cartItemId}', name: 'cart_item_change', methods: ['PUT'])]
    public function change(Request $request, int $cartItemId): JsonResponse
    {
        $payload = $request->getPayload();

        $count  = $payload->get('count');

        if (null === $count) {
            return new JsonResponse(['errorMessage' => 'The count parameter is required'], Response::HTTP_BAD_REQUEST);
        }

        if ($count <= 0) {
            return new JsonResponse(['errorMessage' => 'Ihe count must be more than 0'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $cartItemDTO = $this->cartManager->changeCartItem($cartItemId, (int) $count);

            return new JsonResponse($cartItemDTO, Response::HTTP_OK);
        } catch (CartItemNotFoundException $exception) {
            return new JsonResponse(['errorMessage' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/cartItems/{userId}', name: 'cart_items_show', methods: ['GET'])]
    public function showItems(int $userId): JsonResponse
    {
        $cartDTO = $this->cartManager->showCartItems($userId);

        if (null === $cartDTO) {
            return new JsonResponse(null, Response::HTTP_OK);
        }

        return new JsonResponse($cartDTO, Response::HTTP_OK);
    }
}
