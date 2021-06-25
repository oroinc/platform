<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Tests\Unit\Security;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WsseAuthenticationBundle\Security\WsseToken;
use Oro\Bundle\WsseAuthenticationBundle\Security\WsseTokenFactory;

class WsseTokenFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate(): void
    {
        $factory = new WsseTokenFactory();
        $user = new User();
        $token = $factory->create($user, 'credentials', 'providerKey', $user->getUserRoles());

        self::assertInstanceOf(WsseToken::class, $token);
    }
}
