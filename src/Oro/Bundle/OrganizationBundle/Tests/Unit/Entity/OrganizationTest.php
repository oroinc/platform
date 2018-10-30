<?php
namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class OrganizationTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    use EntityTestCaseTrait;

    /** @var Organization */
    protected $organization;

    protected function setUp()
    {
        $this->organization = new Organization();
    }

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', 123],
            ['name', 'test'],
            ['description', 'test'],
            ['enabled', true],
            ['createdAt', $now],
            ['updatedAt', $now],
        ];

        $this->assertPropertyAccessors(new Organization(), $properties);
    }

    public function testCollections()
    {
        $collections = [
            ['businessUnits', new BusinessUnit()],
            ['users', new User()]
        ];

        $this->assertPropertyCollections(new Organization(), $collections);
    }

    public function testSerialization()
    {
        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 123]);
        $organization->setName('name');
        $organization->setEnabled(true);

        /** @var Organization $unserializedOrganization */
        $unserializedOrganization = unserialize(serialize($organization));

        self::assertSame(123, $unserializedOrganization->getId());
        self::assertSame('name', $unserializedOrganization->getName());
        self::assertTrue($unserializedOrganization->isEnabled());
    }

    public function testPreUpdate()
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

    public function testPrePersist()
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

    public function testToString()
    {
        $organization = new Organization();
        $organization->setName('TestOrganization');

        self::assertEquals('TestOrganization', (string)$organization);
    }
}
