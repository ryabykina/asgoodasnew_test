<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        /*
         * Here must be implemented the check logic of the request from the client
         */
        return new UserBadge('serviceName');
    }
}
