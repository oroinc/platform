<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Token;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeToken;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Component\Testing\Unit\EntityTrait;

class OrganizationRememberMeTokenTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    public function testGetOrganization()
    {
        /** @var User $user */
        $user = $this->getEntity(User::class, ['id' => 1]);
        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 3]);

        $token = new OrganizationRememberMeToken($user, 'user_provider', 'secret', $organization);

        self::assertSame($organization, $token->getOrganization());
    }

    public function testSerialization()
    {
        /** @var User $user */
        $user = $this->getEntity(User::class, ['id' => 1]);
        $providerKey = 'user_provider';
        $secret = 'secret';
        /** @var Role $role */
        $role = $this->getEntity(Role::class, ['id' => 2]);
        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 3]);

        $user->setUserRoles(new ArrayCollection([$role]));

        $token = new OrganizationRememberMeToken($user, $providerKey, $secret, $organization);

        /** @var OrganizationRememberMeToken $newToken */
        $newToken = unserialize(serialize($token));

        self::assertNotSame($token->getUser(), $newToken->getUser());
        self::assertEquals($token->getUser()->getId(), $newToken->getUser()->getId());

        self::assertEquals($token->getProviderKey(), $newToken->getProviderKey());

        self::assertEquals($token->getSecret(), $newToken->getSecret());

        self::assertNotSame($token->getRoles()[0], $newToken->getRoles()[0]);
        self::assertEquals($token->getRoles()[0]->getId(), $newToken->getRoles()[0]->getId());

        self::assertNotSame($token->getOrganization(), $newToken->getOrganization());
        self::assertEquals($token->getOrganization()->getId(), $newToken->getOrganization()->getId());
    }
}
