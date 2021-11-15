<?php

namespace Oro\Bundle\SSOBundle\Tests\Unit\Security;

use Oro\Bundle\SSOBundle\Security\OAuthToken;
use Oro\Bundle\SSOBundle\Security\OAuthTokenFactory;

class OAuthTokenFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $factory = new OAuthTokenFactory();
        $token = $factory->create('accessToken');

        $this->assertInstanceOf(OAuthToken::class, $token);
        $this->assertEquals('accessToken', $token->getAccessToken());
    }
}
