<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class OrganizationTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        self::assertPropertyAccessors(new Organization(), [
            ['id', 123],
            ['name', 'test'],
            ['description', 'test'],
            ['enabled', true],
            ['createdAt', new \DateTime('now')],
            ['updatedAt', new \DateTime('now')],
        ]);
    }

    public function testCollections(): void
    {
        self::assertPropertyCollections(new Organization(), [
            ['businessUnits', new BusinessUnit()],
            ['users', new User()]
        ]);
    }

    public function testShouldBeEnabledByDefault(): void
    {
        $organization = new Organization();
        self::assertTrue($organization->isEnabled());
    }

    public function testSerialization(): void
    {
        $organization = new Organization();
        $organization->setId(123);
        $organization->setName('name');
        $organization->setEnabled(true);

        /** @var Organization $unserializedOrganization */
        $unserializedOrganization = unserialize(serialize($organization));

        self::assertSame(123, $unserializedOrganization->getId());
        self::assertSame('name', $unserializedOrganization->getName());
        self::assertTrue($unserializedOrganization->isEnabled());
    }

    public function testPreUpdate(): void
    {
        $organization = new Organization();

        self::assertNull($organization->getUpdatedAt());

        $organization->preUpdate();

        $updatedAt = $organization->getUpdatedAt();
        self::assertInstanceOf(\DateTime::class, $updatedAt);

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        self::assertLessThanOrEqual($now, $updatedAt);

        self::assertNull($organization->getCreatedAt());
    }

    public function testPrePersist(): void
    {
        $organization = new Organization();

        self::assertNull($organization->getCreatedAt());
        self::assertNull($organization->getUpdatedAt());

        $organization->prePersist();

        $createdAt = $organization->getCreatedAt();
        $updatedAt = $organization->getUpdatedAt();

        self::assertInstanceOf(\DateTime::class, $organization->getCreatedAt());
        self::assertInstanceOf(\DateTime::class, $organization->getUpdatedAt());

        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        self::assertLessThanOrEqual($now, $createdAt);
        self::assertLessThanOrEqual($now, $updatedAt);
    }

    public function testToString(): void
    {
        $organization = new Organization();
        $organization->setName('TestOrganization');

        self::assertEquals('TestOrganization', (string)$organization);
    }
}
