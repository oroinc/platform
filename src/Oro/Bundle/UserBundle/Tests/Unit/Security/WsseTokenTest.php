<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Token\OrganizationContextTrait;
use Oro\Bundle\UserBundle\Security\WsseToken;

class WsseTokenTest extends \PHPUnit_Framework_TestCase
{
    use OrganizationContextTrait;

    public function testOrganizationContextSerialization(): void
    {
        $token = new WsseToken([]);

        $this->assertOrganizationContextSerialization($token);
    }
}
