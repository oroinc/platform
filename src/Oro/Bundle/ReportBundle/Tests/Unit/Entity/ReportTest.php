<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Entity\ReportType;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class ReportTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testProperties(): void
    {
        $properties = [
            ['id', 123],
            ['name', 'test_name'],
            ['description', 'test_description'],
            ['type', new ReportType('test_type')],
            ['entity', \stdClass::class],
            ['owner', new BusinessUnit()],
            ['definition', 'test_definition'],
            ['chartOptions', ['test' => 'data']],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
            ['organization', new Organization()],
        ];

        $this->assertPropertyAccessors(new Report(), $properties);
    }

    public function testClone(): void
    {
        /** @var Report $entity */
        $entity = $this->getEntity(
            Report::class,
            [
                'id' => 123,
                'name' => 'test_name',
                'description' => 'test_description',
                'type' => new ReportType('test_type'),
                'entity' => \stdClass::class,
                'owner' => new BusinessUnit(),
                'definition' => 'test_definition',
                'chartOptions' => ['test' => 'data'],
                'createdAt' => new \DateTime(),
                'updatedAt' => new \DateTime(),
                'organization' => new Organization(),
            ]
        );

        $this->assertNotEmpty($entity->getId());
        $this->assertNotEmpty($entity->getName());
        $this->assertNotEmpty($entity->getDescription());
        $this->assertNotEmpty($entity->getType());
        $this->assertNotEmpty($entity->getEntity());
        $this->assertNotEmpty($entity->getOwner());
        $this->assertNotEmpty($entity->getDefinition());
        $this->assertNotEmpty($entity->getChartOptions());
        $this->assertNotEmpty($entity->getCreatedAt());
        $this->assertNotEmpty($entity->getUpdatedAt());
        $this->assertNotEmpty($entity->getOrganization());

        /** @var Report $newEntity */
        $newEntity = clone $entity;

        $this->assertNull($newEntity->getId());
        $this->assertSame($entity->getName(), $newEntity->getName());
        $this->assertSame($entity->getDescription(), $newEntity->getDescription());
        $this->assertSame($entity->getType(), $newEntity->getType());
        $this->assertSame($entity->getEntity(), $newEntity->getEntity());
        $this->assertSame($entity->getOwner(), $newEntity->getOwner());
        $this->assertSame($entity->getDefinition(), $newEntity->getDefinition());
        $this->assertSame($entity->getChartOptions(), $newEntity->getChartOptions());
        $this->assertNull($newEntity->getCreatedAt());
        $this->assertNull($newEntity->getUpdatedAt());
        $this->assertSame($entity->getOrganization(), $newEntity->getOrganization());
    }
}
