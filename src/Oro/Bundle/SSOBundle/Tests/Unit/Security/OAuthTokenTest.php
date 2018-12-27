<?php

namespace Oro\Bundle\SSOBundle\Tests\Unit\Security;

use Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Token\OrganizationContextTrait;
use Oro\Bundle\SSOBundle\Security\OAuthToken;

class OAuthTokenTest extends \PHPUnit\Framework\TestCase
{
    use OrganizationContextTrait;

    public function testOrganizationContextSerialization(): void
    {
        $token = new OAuthToken('access_token', []);

        $this->assertOrganizationContextSerialization($token);
    }
}
