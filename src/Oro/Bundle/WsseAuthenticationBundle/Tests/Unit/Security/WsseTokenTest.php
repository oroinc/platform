<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Tests\Unit\Security;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\WsseAuthenticationBundle\Security\WsseToken;
use Oro\Component\Testing\Unit\EntityTrait;

class WsseTokenTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    public function testGetOrganization()
    {
        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 3]);

        $token = new WsseToken('user', 'pass', 'user_provider');
        $token->setOrganization($organization);

        self::assertSame($organization, $token->getOrganization());
    }

    public function testSerialization()
    {
        $user = 'user';
        $credentials = 'pass';
        $providerKey = 'user_provider';
        /** @var Role $role */
        $role = $this->getEntity(Role::class, ['id' => 2]);
        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 3]);

        $token = new WsseToken($user, $credentials, $providerKey, [$role]);
        $token->setOrganization($organization);

        /** @var WsseToken $newToken */
        $newToken = unserialize(serialize($token));

        self::assertEquals($token->getUser(), $newToken->getUser());

        self::assertEquals($token->getCredentials(), $newToken->getCredentials());

        self::assertEquals($token->getProviderKey(), $newToken->getProviderKey());

        self::assertNotSame($token->getRoles()[0], $newToken->getRoles()[0]);
        self::assertEquals($token->getRoles()[0]->getId(), $newToken->getRoles()[0]->getId());

        self::assertNotSame($token->getOrganization(), $newToken->getOrganization());
        self::assertEquals($token->getOrganization()->getId(), $newToken->getOrganization()->getId());
    }
}
