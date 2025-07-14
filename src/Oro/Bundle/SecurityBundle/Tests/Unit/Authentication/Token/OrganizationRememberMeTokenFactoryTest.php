<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeTokenFactory;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\TestCase;

class OrganizationRememberMeTokenFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $user = new User();
        $organization = new Organization();
        $factory = new OrganizationRememberMeTokenFactory();
        $token = $factory->create($user, 'testProvider', 'testKey', $organization);

        $this->assertInstanceOf(OrganizationRememberMeToken::class, $token);
        $this->assertEquals($user, $token->getUser());
        $this->assertEquals($organization, $token->getOrganization());
        $this->assertEquals('testProvider', $token->getFirewallName());
        $this->assertEquals('testKey', $token->getSecret());
    }
}
