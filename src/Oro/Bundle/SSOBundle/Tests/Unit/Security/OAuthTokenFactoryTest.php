<?php

namespace Oro\Bundle\SSOBundle\Tests\Unit\Security;

use Oro\Bundle\SSOBundle\Security\OAuthTokenFactory;

class OAuthTokenFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $factory = new OAuthTokenFactory();
        $token = $factory->create('accessToken');

        $this->assertInstanceOf('Oro\Bundle\SSOBundle\Security\OAuthToken', $token);
        $this->assertEquals('accessToken', $token->getAccessToken());
    }
}
