<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Oro\Bundle\UserBundle\Security\WsseTokenFactory;

class WsseTokenFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $factory = new WsseTokenFactory();
        $token = $factory->create();

        $this->assertInstanceOf('Oro\Bundle\UserBundle\Security\WsseToken', $token);
    }
}
