<?php

namespace Oro\Bundle\SSOBundle\Tests\Unit\Security;

use Oro\Bundle\SSOBundle\Security\OAuthToken;
use Oro\Bundle\SSOBundle\Security\OAuthTokenFactory;
use PHPUnit\Framework\TestCase;

class OAuthTokenFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $factory = new OAuthTokenFactory();
        $token = $factory->create('accessToken');

        $this->assertInstanceOf(OAuthToken::class, $token);
        $this->assertEquals('accessToken', $token->getAccessToken());
    }
}
