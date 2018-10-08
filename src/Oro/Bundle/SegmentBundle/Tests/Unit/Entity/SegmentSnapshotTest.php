<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Entity;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;

class SegmentSnapshotTest extends \PHPUnit\Framework\TestCase
{
    /** @var SegmentSnapshot */
    protected $entity;

    /** @var Segment */
    protected $segment;

    protected function setUp()
    {
        $this->segment = new Segment();
        $this->entity  = new SegmentSnapshot($this->segment);
    }

    protected function tearDown()
    {
        unset($this->segment, $this->entity);
    }

    public function testSettersAndGetters()
    {
        $this->assertNull($this->entity->getId());
        $this->assertNull($this->entity->getCreatedAt());
        $this->assertNull($this->entity->getEntityId());
        $this->assertNull($this->entity->getIntegerEntityId());
        $this->assertNotNull($this->entity->getSegment());

        $testEntityId        = 12;
        $testIntegerEntityId = 13;
        $testCreatedAt       = new \DateTime('now - 1 day', new \DateTimeZone('UTC'));
        $this->entity->setEntityId($testEntityId);
        $this->entity->setIntegerEntityId($testIntegerEntityId);
        $this->entity->setCreatedAt($testCreatedAt);

        $this->assertSame($testEntityId, $this->entity->getEntityId());
        $this->assertSame($testIntegerEntityId, $this->entity->getIntegerEntityId());
        $this->assertSame($testCreatedAt, $this->entity->getCreatedAt());

        $this->entity->prePersist();
        $this->assertNotSame($testCreatedAt, $this->entity->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $this->entity->getCreatedAt());
    }
}
