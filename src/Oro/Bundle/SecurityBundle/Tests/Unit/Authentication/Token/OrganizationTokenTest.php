<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Component\Testing\Unit\EntityTrait;

class OrganizationTokenTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    public function testGetters()
    {
        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 3]);

        $token = new OrganizationToken($organization);

        self::assertSame($organization, $token->getOrganization());
        self::assertSame('', $token->getCredentials());
    }

    public function testSerialization()
    {
        /** @var Role $role */
        $role = $this->getEntity(Role::class, ['id' => 2]);
        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 3]);

        $token = new OrganizationToken($organization, [$role]);

        /** @var OrganizationToken $newToken */
        $newToken = unserialize(serialize($token));

        self::assertNotSame($token->getRoles()[0], $newToken->getRoles()[0]);
        self::assertEquals($token->getRoles()[0]->getId(), $newToken->getRoles()[0]->getId());

        self::assertNotSame($token->getOrganization(), $newToken->getOrganization());
        self::assertEquals($token->getOrganization()->getId(), $newToken->getOrganization()->getId());
    }
}
