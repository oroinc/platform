<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Token\OrganizationContextTrait;
use Oro\Bundle\UserBundle\Security\WsseToken;

class WsseTokenTest extends \PHPUnit\Framework\TestCase
{
    use OrganizationContextTrait;

    public function testOrganizationContextSerialization(): void
    {
        $token = new WsseToken('user', 'pass', 'user_provider');

        $this->assertOrganizationContextSerialization($token);
    }
}
