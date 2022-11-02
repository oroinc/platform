<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\ConsoleToken;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Component\Testing\Unit\EntityTrait;

class ConsoleTokenTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    public function testGetCredentials()
    {
        $token = new ConsoleToken();
        $this->assertEmpty($token->getCredentials());
    }

    public function testSetGetOrganization()
    {
        $organization = new Organization();

        $token = new ConsoleToken();
        $token->setOrganization($organization);

        $this->assertSame($organization, $token->getOrganization());
    }

    public function testSerialization()
    {
        /** @var Role $role */
        $role = $this->getEntity(Role::class, ['id' => 2]);
        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 3]);

        $token = new ConsoleToken([$role]);
        $token->setOrganization($organization);

        /** @var ConsoleToken $newToken */
        $newToken = unserialize(serialize($token));

        self::assertNotSame($token->getRoles()[0], $newToken->getRoles()[0]);
        self::assertEquals($token->getRoles()[0]->getId(), $newToken->getRoles()[0]->getId());

        self::assertNotSame($token->getOrganization(), $newToken->getOrganization());
        self::assertEquals($token->getOrganization()->getId(), $newToken->getOrganization()->getId());
    }
}
