<?php

namespace Oro\Bundle\SSOBundle\Tests\Unit\Security;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SSOBundle\Security\OAuthToken;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Component\Testing\Unit\EntityTrait;

class OAuthTokenTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    public function testGetOrganization()
    {
        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 3]);

        $token = new OAuthToken('access_token');
        $token->setOrganization($organization);

        self::assertSame($organization, $token->getOrganization());
    }

    public function testSerialization()
    {
        $accessToken = 'access_token';
        /** @var Role $role */
        $role = $this->getEntity(Role::class, ['id' => 2]);
        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 3]);

        $token = new OAuthToken($accessToken, [$role]);
        $token->setOrganization($organization);

        /** @var OAuthToken $newToken */
        $newToken = unserialize(serialize($token));

        self::assertEquals($token->getAccessToken(), $newToken->getAccessToken());

        self::assertNotSame($token->getRoles()[0], $newToken->getRoles()[0]);
        self::assertEquals($token->getRoles()[0]->getId(), $newToken->getRoles()[0]->getId());

        self::assertNotSame($token->getOrganization(), $newToken->getOrganization());
        self::assertEquals($token->getOrganization()->getId(), $newToken->getOrganization()->getId());
    }
}
