<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeTokenFactory;
use Oro\Bundle\UserBundle\Entity\User;

class OrganizationRememberMeTokenFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $user = new User();
        $organization = new Organization();
        $factory = new OrganizationRememberMeTokenFactory();
        $token = $factory->create($user, 'testProvider', 'testKey', $organization);

        $this->assertInstanceOf('Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeToken', $token);
        $this->assertEquals($user, $token->getUser());
        $this->assertEquals($organization, $token->getOrganizationContext());
        $this->assertEquals('testProvider', $token->getProviderKey());
        $this->assertEquals('testKey', $token->getKey());
    }
}
