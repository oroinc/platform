<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactory;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use PHPUnit\Framework\TestCase;

class UsernamePasswordOrganizationTokenFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $user = $this->createMock(AbstractUser::class);
        $organization = new Organization();
        $factory = new UsernamePasswordOrganizationTokenFactory();
        $token = $factory->create($user, 'main', $organization);

        $this->assertInstanceOf(UsernamePasswordOrganizationToken::class, $token);
        $this->assertEquals($organization, $token->getOrganization());
        $this->assertEquals($user, $token->getUser());
    }
}
