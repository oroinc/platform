<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactory;

class UsernamePasswordOrganizationTokenFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $organization = new Organization();
        $factory = new UsernamePasswordOrganizationTokenFactory();
        $token = $factory->create('username', 'credentials', 'testProvider', $organization);

        $this->assertInstanceOf(UsernamePasswordOrganizationToken::class, $token);
        $this->assertEquals($organization, $token->getOrganization());
        $this->assertEquals('username', $token->getUser());
        $this->assertEquals('credentials', $token->getCredentials());
        $this->assertEquals('testProvider', $token->getProviderKey());
    }
}
