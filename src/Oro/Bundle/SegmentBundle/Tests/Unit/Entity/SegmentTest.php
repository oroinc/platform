<?php
namespace Oro\Bundle\SegmentBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class SegmentTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    /** @var Segment */
    private $entity;

    protected function setUp(): void
    {
        $this->entity = new Segment();
    }

    public function testSettersAndGetters()
    {
        $this->assertNull($this->entity->getId());
        $this->assertNull($this->entity->getName());
        $this->assertNull($this->entity->getNameLowercase());
        $this->assertNull($this->entity->getDefinition());
        $this->assertNull($this->entity->getDescription());
        $this->assertNull($this->entity->getType());
        $this->assertNull($this->entity->getEntity());
        $this->assertNull($this->entity->getOwner());
        $this->assertNull($this->entity->getCreatedAt());
        $this->assertNull($this->entity->getUpdatedAt());
        $this->assertNull($this->entity->getLastRun());
        $this->assertNull($this->entity->getOrganization());
        $this->assertNull($this->entity->getRecordsLimit());

        $testData = uniqid('name');
        $this->entity->setName($testData);
        $this->assertEquals($testData, $this->entity->getName());

        $testData = uniqid('definition');
        $this->entity->setDefinition($testData);
        $this->assertEquals($testData, $this->entity->getDefinition());

        $testData = uniqid('description');
        $this->entity->setDescription($testData);
        $this->assertEquals($testData, $this->entity->getDescription());

        $testData = $this->createMock(SegmentType::class, [], ['testTypeName']);
        $this->entity->setType($testData);
        $this->assertSame($testData, $this->entity->getType());

        $testData = $this->createMock(BusinessUnit::class);
        $this->entity->setOwner($testData);
        $this->assertSame($testData, $this->entity->getOwner());

        $testData = uniqid('entity');
        $this->entity->setEntity($testData);
        $this->assertEquals($testData, $this->entity->getEntity());

        $testData = new \DateTime();
        $this->entity->setCreatedAt($testData);
        $this->assertSame($testData, $this->entity->getCreatedAt());

        $testData = new \DateTime();
        $this->entity->setUpdatedAt($testData);
        $this->assertSame($testData, $this->entity->getUpdatedAt());

        $testData = new \DateTime();
        $this->entity->setLastRun($testData);
        $this->assertSame($testData, $this->entity->getLastRun());

        $testData = $this->createMock(Organization::class);
        $this->entity->setOrganization($testData);
        $this->assertSame($testData, $this->entity->getOrganization());

        $testData = 10;
        $this->entity->setRecordsLimit($testData);
        $this->assertSame($testData, $this->entity->getRecordsLimit());
    }

    public function testLifecycleCallbacks()
    {
        $segment = new Segment();

        $segment->beforeSave();
        $this->assertInstanceOf(\DateTime::class, $segment->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $segment->getUpdatedAt());
        $this->assertEquals($segment->getCreatedAt(), $segment->getUpdatedAt());

        $segment = new Segment();
        $segment->doUpdate();
        $this->assertEmpty($segment->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $segment->getUpdatedAt());
    }

    public function testIsDynamicFalse()
    {
        $segmentType = new SegmentType(SegmentType::TYPE_STATIC);
        $segment = new Segment();

        $this->assertFalse($segment->isDynamic());
        $segment->setType($segmentType);
        $this->assertFalse($segment->isDynamic());
    }

    public function testIsDynamicTrue()
    {
        $segmentType = new SegmentType(SegmentType::TYPE_DYNAMIC);
        $segment = new Segment();
        $segment->setType($segmentType);

        $this->assertTrue($segment->isDynamic());
    }

    public function testClone(): void
    {
        /** @var Segment $entity */
        $entity = $this->getEntity(
            Segment::class,
            [
                'id' => 123,
                'name' => 'test_name',
                'description' => 'test_description',
                'type' => new SegmentType('test_type'),
                'entity' => \stdClass::class,
                'definition' => 'test_definition',
                'owner' => new BusinessUnit(),
                'lastRun' => new \DateTime(),
                'createdAt' => new \DateTime(),
                'updatedAt' => new \DateTime(),
                'organization' => new Organization(),
                'recordsLimit' => 456,
            ]
        );

        $this->assertNotEmpty($entity->getId());
        $this->assertNotEmpty($entity->getName());
        $this->assertNotEmpty($entity->getNameLowercase());
        $this->assertNotEmpty($entity->getDescription());
        $this->assertNotEmpty($entity->getType());
        $this->assertNotEmpty($entity->getEntity());
        $this->assertNotEmpty($entity->getOwner());
        $this->assertNotEmpty($entity->getDefinition());
        $this->assertNotEmpty($entity->getLastRun());
        $this->assertNotEmpty($entity->getCreatedAt());
        $this->assertNotEmpty($entity->getUpdatedAt());
        $this->assertNotEmpty($entity->getOrganization());
        $this->assertNotEmpty($entity->getRecordsLimit());

        /** @var Segment $newEntity */
        $newEntity = clone $entity;

        $this->assertNull($newEntity->getId());
        $this->assertSame($entity->getName(), $newEntity->getName());
        $this->assertSame($entity->getDescription(), $newEntity->getDescription());
        $this->assertSame($entity->getType(), $newEntity->getType());
        $this->assertSame($entity->getEntity(), $newEntity->getEntity());
        $this->assertSame($entity->getOwner(), $newEntity->getOwner());
        $this->assertSame($entity->getDefinition(), $newEntity->getDefinition());
        $this->assertNull($newEntity->getLastRun());
        $this->assertNull($newEntity->getCreatedAt());
        $this->assertNull($newEntity->getUpdatedAt());
        $this->assertSame($entity->getOrganization(), $newEntity->getOrganization());
        $this->assertSame($entity->getRecordsLimit(), $newEntity->getRecordsLimit());
    }
}
