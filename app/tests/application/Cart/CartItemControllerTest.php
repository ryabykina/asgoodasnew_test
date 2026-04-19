<?php

declare(strict_types=1);

namespace App\Tests\Application\Cart;

use App\DTO\Cart\CartDTO;
use App\DTO\Cart\CartItemDTO;
use App\Service\Cart\CartManagerInterface;
use App\Service\Cart\Exception\CartItemNotFoundException;
use App\Service\Cart\Exception\ItemByUserExistsException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CartItemControllerTest extends WebTestCase
{
    private const AUTH_HEADERS = ['HTTP_AUTHORIZATION' => 'Bearer test-token', 'CONTENT_TYPE' => 'application/json'];

    private function buildCartDTO(int $userId, int $cartItemId, int $itemId, int $count): CartDTO
    {
        $cartItemDTO = new CartItemDTO();
        $cartItemDTO->id = $cartItemId;
        $cartItemDTO->itemId = $itemId;
        $cartItemDTO->count = $count;

        $cartDTO = new CartDTO();
        $cartDTO->userId = $userId;
        $cartDTO->items = [$cartItemDTO];

        return $cartDTO;
    }

    private function buildCartItemDTO(int $cartItemId, int $itemId, int $count): CartItemDTO
    {
        $cartItemDTO = new CartItemDTO();
        $cartItemDTO->id = $cartItemId;
        $cartItemDTO->itemId = $itemId;
        $cartItemDTO->count = $count;

        return $cartItemDTO;
    }

    // --- add() ---

    public function testAddCartItemSuccessfully(): void
    {
        $client = static::createClient();

        $cartDTO = $this->buildCartDTO(userId: 1, cartItemId: 10, itemId: 5, count: 3);

        $mockCartManager = $this->createMock(CartManagerInterface::class);
        $mockCartManager
            ->expects($this->once())
            ->method('saveCartItem')
            ->with(1, 5, 3)
            ->willReturn($cartDTO);

        static::getContainer()->set(CartManagerInterface::class, $mockCartManager);

        $content = json_encode(['userId' => 1, 'itemId' => 5, 'count' => 3]);

        $client->request(
            method: 'POST',
            uri: '/cartItem',
            content: $content ?: null,
            server: self::AUTH_HEADERS,
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $responseData = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(1, $responseData['userId']);
        $this->assertCount(1, $responseData['items']);
        $this->assertSame(5, $responseData['items'][0]['itemId']);
        $this->assertSame(3, $responseData['items'][0]['count']);
    }

    public function testAddCartItemUserIdDoesntExist(): void
    {
        $client = static::createClient();

        $content = json_encode(['itemId' => 5, 'count' => 3]);

        $client->request(
            method: 'POST',
            uri: '/cartItem',
            content: $content ?: null,
            server: self::AUTH_HEADERS,
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseData = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errorMessage', $responseData);
    }

    public function testAddCartItemItemIdDoesntExist(): void
    {
        $client = static::createClient();

        $content = json_encode(['userId' => 1, 'count' => 3]);

        $client->request(
            method: 'POST',
            uri: '/cartItem',
            content: $content ?: null,
            server: self::AUTH_HEADERS,
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testAddCartItemCartItemIdExists(): void
    {
        $client = static::createClient();

        $mockCartManager = $this->createMock(CartManagerInterface::class);
        $mockCartManager
            ->expects($this->once())
            ->method('saveCartItem')
            ->with(1, 5, 3)
            ->willThrowException(new ItemByUserExistsException('The itemId = 5 for the userId = 1 exists'));

        static::getContainer()->set(CartManagerInterface::class, $mockCartManager);

        $content = json_encode(['userId' => 1, 'itemId' => 5, 'count' => 3]);

        $client->request(
            method: 'POST',
            uri: '/cartItem',
            content: $content ?: null,
            server: self::AUTH_HEADERS,
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);

        $responseData = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errorMessage', $responseData);
    }

    public function testAddCartItemCountDoesntExist(): void
    {
        $client = static::createClient();

        $content = json_encode(['userId' => 1, 'itemId' => 5]);

        $client->request(
            method: 'POST',
            uri: '/cartItem',
            content: $content ?: null,
            server: self::AUTH_HEADERS,
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testAddCartItemCountLessThanOne(): void
    {
        $client = static::createClient();

        $content = json_encode(['userId' => 1, 'itemId' => 5, 'count' => 0]);

        $client->request(
            method: 'POST',
            uri: '/cartItem',
            content: $content ?: null,
            server: self::AUTH_HEADERS,
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }


    // --- remove() ---

    public function testRemoveCartItemSuccessfully(): void
    {
        $client = static::createClient();

        $mockCartManager = $this->createMock(CartManagerInterface::class);
        $mockCartManager
            ->expects($this->once())
            ->method('removeCartItem')
            ->with(42)
            ->willReturn(true);

        static::getContainer()->set(CartManagerInterface::class, $mockCartManager);

        $client->request(
            method: 'DELETE',
            uri: '/cartItem/42',
            server: ['HTTP_AUTHORIZATION' => 'Bearer test-token'],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testRemoveCartItemWrongItemId(): void
    {
        $client = static::createClient();

        $mockCartManager = $this->createMock(CartManagerInterface::class);
        $mockCartManager
            ->expects($this->once())
            ->method('removeCartItem')
            ->with(999)
            ->willReturn(false);

        static::getContainer()->set(CartManagerInterface::class, $mockCartManager);

        $client->request(
            method: 'DELETE',
            uri: '/cartItem/999',
            server: ['HTTP_AUTHORIZATION' => 'Bearer test-token'],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $responseData = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errorMessage', $responseData);
    }

    // --- change() ---

    public function testChangeCartItemSuccessfully(): void
    {
        $client = static::createClient();

        $cartItemDTO = $this->buildCartItemDTO(cartItemId: 42, itemId: 5, count: 10);

        $mockCartManager = $this->createMock(CartManagerInterface::class);
        $mockCartManager
            ->expects($this->once())
            ->method('changeCartItem')
            ->with(42, 10)
            ->willReturn($cartItemDTO);

        static::getContainer()->set(CartManagerInterface::class, $mockCartManager);

        $content = json_encode(['count' => 10]);

        $client->request(
            method: 'PUT',
            uri: '/cartItem/42',
            content: $content ?: null,
            server: self::AUTH_HEADERS,
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseData = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(42, $responseData['id']);
        $this->assertSame(5, $responseData['itemId']);
        $this->assertSame(10, $responseData['count']);
    }

    public function testChangeCartItemWrongItemId(): void
    {
        $client = static::createClient();

        $mockCartManager = $this->createMock(CartManagerInterface::class);
        $mockCartManager
            ->expects($this->once())
            ->method('changeCartItem')
            ->with(999, 10)
            ->willThrowException(new CartItemNotFoundException('The cartItemId = 999 does not exist'));

        static::getContainer()->set(CartManagerInterface::class, $mockCartManager);

        $content = json_encode(['count' => 10]);

        $client->request(
            method: 'PUT',
            uri: '/cartItem/999',
            content: $content ?: null,
            server: self::AUTH_HEADERS,
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $responseData = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errorMessage', $responseData);
    }

    public function testChangeCartItemNoCount(): void
    {
        $client = static::createClient();

        $content = json_encode(['someOtherField' => 'value']);

        $client->request(
            method: 'PUT',
            uri: '/cartItem/42',
            content: $content ?: null,
            server: self::AUTH_HEADERS,
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testChangeCartItemCountIsLessThanOne(): void
    {
        $client = static::createClient();

        $content = json_encode(['count' => 0]);

        $client->request(
            method: 'PUT',
            uri: '/cartItem/42',
            content: $content ?: null,
            server: self::AUTH_HEADERS,
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // --- showItems() ---

    public function testShowCartItemsSuccessfully(): void
    {
        $client = static::createClient();

        $cartDTO = $this->buildCartDTO(userId: 1, cartItemId: 10, itemId: 5, count: 3);

        $mockCartManager = $this->createMock(CartManagerInterface::class);
        $mockCartManager
            ->expects($this->once())
            ->method('showCartItems')
            ->with(1)
            ->willReturn($cartDTO);

        static::getContainer()->set(CartManagerInterface::class, $mockCartManager);

        $client->request(
            method: 'GET',
            uri: '/cartItems/1',
            server: ['HTTP_AUTHORIZATION' => 'Bearer test-token'],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseData = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(1, $responseData['userId']);
        $this->assertNotEmpty($responseData['items']);
    }

    public function testShowCartItemsNoItems(): void
    {
        $client = static::createClient();

        $mockCartManager = $this->createMock(CartManagerInterface::class);
        $mockCartManager
            ->expects($this->once())
            ->method('showCartItems')
            ->with(1)
            ->willReturn(null);

        static::getContainer()->set(CartManagerInterface::class, $mockCartManager);

        $client->request(
            method: 'GET',
            uri: '/cartItems/1',
            server: ['HTTP_AUTHORIZATION' => 'Bearer test-token'],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseContent = $client->getResponse()->getContent();
        $this->assertSame('{}', $responseContent);
    }
}
