<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Token;

use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Component\Testing\Unit\EntityTrait;

class UsernamePasswordOrganizationTokenTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    public function testGetOrganization()
    {
        /** @var User $user */
        $user = $this->getEntity(User::class, ['id' => 1]);
        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 3]);

        $token = new UsernamePasswordOrganizationToken($user, 'pass', 'user_provider', $organization);

        self::assertSame($organization, $token->getOrganization());
    }

    public function testSerialization()
    {
        /** @var User $user */
        $user = $this->getEntity(User::class, ['id' => 1]);
        $credentials = 'pass';
        $providerKey = 'user_provider';
        /** @var Role $role */
        $role = $this->getEntity(Role::class, ['id' => 2]);
        $user->addUserRole($role);
        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 3]);

        $token = new UsernamePasswordOrganizationToken($user, $credentials, $providerKey, $organization, [$role]);

        /** @var UsernamePasswordOrganizationToken $newToken */
        $newToken = unserialize(serialize($token));

        self::assertNotSame($token->getUser(), $newToken->getUser());
        self::assertEquals($token->getUser()->getId(), $newToken->getUser()->getId());

        self::assertEquals($token->getCredentials(), $newToken->getCredentials());

        self::assertEquals($token->getProviderKey(), $newToken->getProviderKey());

        self::assertNotSame($token->getRoles()[0], $newToken->getRoles()[0]);
        self::assertEquals($token->getRoles()[0]->getId(), $newToken->getRoles()[0]->getId());

        self::assertNotSame($token->getOrganization(), $newToken->getOrganization());
        self::assertEquals($token->getOrganization()->getId(), $newToken->getOrganization()->getId());
    }
}
