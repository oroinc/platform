<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Entity\ReportType;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ReportTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testSettersAndGetters(): void
    {
        $properties = [
            ['id', 123],
            ['name', 'test_name'],
            ['description', 'test_description'],
            ['type', new ReportType('test_type')],
            ['entity', \stdClass::class],
            ['definition', 'test_definition'],
            ['chartOptions', ['test' => 'data']],
            ['owner', new BusinessUnit()],
            ['organization', new Organization()],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ];

        $this->assertPropertyAccessors(new Report(), $properties);
    }

    public function testPrePersist(): void
    {
        $entity = new Report();
        $entity->beforeSave();
        $this->assertInstanceOf(\DateTime::class, $entity->getCreatedAt());
        $this->assertEquals($entity->getCreatedAt(), $entity->getUpdatedAt());
        $this->assertNotSame($entity->getCreatedAt(), $entity->getUpdatedAt());
    }

    public function testPreUpdate(): void
    {
        $entity = new Report();
        $entity->beforeUpdate();
        $this->assertInstanceOf(\DateTime::class, $entity->getUpdatedAt());
    }

    public function testClone(): void
    {
        $entity = new Report();
        ReflectionUtil::setId($entity, 123);
        $entity->setName('test_name');
        $entity->setDescription('test_description');
        $entity->setType(new ReportType('test_type'));
        $entity->setEntity(\stdClass::class);
        $entity->setDefinition('test_definition');
        $entity->setChartOptions(['test' => 'data']);
        $entity->setOwner(new BusinessUnit());
        $entity->setOrganization(new Organization());
        $entity->beforeSave();

        $clonedEntity = clone $entity;
        $this->assertNull($clonedEntity->getId());
        $this->assertSame($entity->getName(), $clonedEntity->getName());
        $this->assertSame($entity->getDescription(), $clonedEntity->getDescription());
        $this->assertSame($entity->getType(), $clonedEntity->getType());
        $this->assertSame($entity->getEntity(), $clonedEntity->getEntity());
        $this->assertSame($entity->getDefinition(), $clonedEntity->getDefinition());
        $this->assertSame($entity->getChartOptions(), $clonedEntity->getChartOptions());
        $this->assertSame($entity->getOwner(), $clonedEntity->getOwner());
        $this->assertSame($entity->getOrganization(), $clonedEntity->getOrganization());
        $this->assertNull($clonedEntity->getCreatedAt());
        $this->assertNull($clonedEntity->getUpdatedAt());
    }
}
