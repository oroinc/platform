<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Security\WsseTokenFactory;

class WsseTokenFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $factory = new WsseTokenFactory();
        $user = new User();
        $token = $factory->create($user, 'credentials', 'providerKey', $user->getRoles());

        $this->assertInstanceOf('Oro\Bundle\UserBundle\Security\WsseToken', $token);
    }
}
