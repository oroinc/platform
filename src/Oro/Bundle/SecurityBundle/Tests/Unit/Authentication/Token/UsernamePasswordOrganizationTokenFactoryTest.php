<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactory;

class UsernamePasswordOrganizationTokenFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $organization = new Organization();
        $factory = new UsernamePasswordOrganizationTokenFactory();
        $token = $factory->create('username', 'credentials', 'testProvider', $organization);

        $this->assertInstanceOf(
            'Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken',
            $token
        );
        $this->assertEquals($organization, $token->getOrganizationContext());
        $this->assertEquals('username', $token->getUser());
        $this->assertEquals('credentials', $token->getCredentials());
        $this->assertEquals('testProvider', $token->getProviderKey());
    }
}
