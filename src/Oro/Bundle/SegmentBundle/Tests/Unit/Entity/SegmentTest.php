<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class SegmentTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testSettersAndGetters(): void
    {
        $properties = [
            ['id', 123],
            ['name', 'test_name'],
            ['description', 'test_description'],
            ['type', new SegmentType('test_type')],
            ['entity', \stdClass::class],
            ['definition', 'test_definition'],
            ['recordsLimit', 123],
            ['owner', new BusinessUnit()],
            ['organization', new Organization()],
            ['lastRun', new \DateTime()],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ];

        $this->assertPropertyAccessors(new Segment(), $properties);
    }

    public function testIsDynamicFalse(): void
    {
        $segment = new Segment();
        $this->assertFalse($segment->isDynamic());

        $segment->setType(new SegmentType(SegmentType::TYPE_STATIC));
        $this->assertFalse($segment->isDynamic());
    }

    public function testIsDynamicTrue(): void
    {
        $segment = new Segment();
        $segment->setType(new SegmentType(SegmentType::TYPE_DYNAMIC));

        $this->assertTrue($segment->isDynamic());
    }

    public function testPrePersist(): void
    {
        $entity = new Segment();
        $entity->beforeSave();
        $this->assertInstanceOf(\DateTime::class, $entity->getCreatedAt());
        $this->assertEquals($entity->getCreatedAt(), $entity->getUpdatedAt());
        $this->assertNotSame($entity->getCreatedAt(), $entity->getUpdatedAt());
    }

    public function testPreUpdate(): void
    {
        $entity = new Segment();
        $entity->doUpdate();
        $this->assertInstanceOf(\DateTime::class, $entity->getUpdatedAt());
    }

    public function testClone(): void
    {
        $entity = new Segment();
        ReflectionUtil::setId($entity, 123);
        $entity->setName('test_name');
        $entity->setDescription('test_description');
        $entity->setType(new SegmentType('test_type'));
        $entity->setEntity(\stdClass::class);
        $entity->setDefinition('test_definition');
        $entity->setRecordsLimit(456);
        $entity->setOwner(new BusinessUnit());
        $entity->setOrganization(new Organization());
        $entity->setLastRun(new \DateTime());
        $entity->beforeSave();

        $clonedEntity = clone $entity;

        $this->assertNull($clonedEntity->getId());
        $this->assertSame($entity->getName(), $clonedEntity->getName());
        $this->assertSame($entity->getDescription(), $clonedEntity->getDescription());
        $this->assertSame($entity->getType(), $clonedEntity->getType());
        $this->assertSame($entity->getEntity(), $clonedEntity->getEntity());
        $this->assertSame($entity->getDefinition(), $clonedEntity->getDefinition());
        $this->assertSame($entity->getRecordsLimit(), $clonedEntity->getRecordsLimit());
        $this->assertSame($entity->getOwner(), $clonedEntity->getOwner());
        $this->assertSame($entity->getOrganization(), $clonedEntity->getOrganization());
        $this->assertNull($clonedEntity->getLastRun());
        $this->assertNull($clonedEntity->getCreatedAt());
        $this->assertNull($clonedEntity->getUpdatedAt());
    }
}
