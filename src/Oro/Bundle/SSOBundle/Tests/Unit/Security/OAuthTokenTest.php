<?php

namespace Oro\Bundle\SSOBundle\Tests\Unit\Security;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SSOBundle\Security\OAuthToken;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class OAuthTokenTest extends TestCase
{
    use EntityTrait;

    public function testGetOrganization(): void
    {
        $organization = $this->getEntity(Organization::class, ['id' => 3]);

        $token = new OAuthToken('access_token');
        $token->setOrganization($organization);

        self::assertSame($organization, $token->getOrganization());
    }

    public function testSerialization(): void
    {
        $accessToken = 'access_token';
        $role = $this->getEntity(Role::class, ['id' => 2]);
        $organization = $this->getEntity(Organization::class, ['id' => 3]);

        $token = new OAuthToken($accessToken, [$role]);
        $token->setOrganization($organization);
        $token->setResourceOwnerName('test');

        /** @var OAuthToken $newToken */
        $newToken = unserialize(serialize($token));

        self::assertEquals($token->getAccessToken(), $newToken->getAccessToken());

        self::assertNotSame($token->getRoles()[0], $newToken->getRoles()[0]);
        self::assertEquals($token->getRoles()[0]->getId(), $newToken->getRoles()[0]->getId());

        self::assertNotSame($token->getOrganization(), $newToken->getOrganization());
        self::assertEquals($token->getOrganization()->getId(), $newToken->getOrganization()->getId());
    }
}
