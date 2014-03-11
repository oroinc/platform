<?php
namespace Oro\Bundle\SegmentBundle\Tests\Unit\Entity;

use Oro\Bundle\SegmentBundle\Entity\Segment;

class SegmentTest extends \PHPUnit_Framework_TestCase
{
    /** @var Segment */
    protected $entity;

    public function setUp()
    {
        $this->entity = new Segment();
    }

    public function tearDown()
    {
        unset($this->entity);
    }

    public function testSettersAndGetters()
    {
        $this->assertNull($this->entity->getId());
        $this->assertNull($this->entity->getName());
        $this->assertNull($this->entity->getDefinition());
        $this->assertNull($this->entity->getDescription());
        $this->assertNull($this->entity->getType());
        $this->assertNull($this->entity->getEntity());
        $this->assertNull($this->entity->getOwner());
        $this->assertNull($this->entity->getCreatedAt());
        $this->assertNull($this->entity->getUpdatedAt());
        $this->assertNull($this->entity->getLastRun());

        $testData = uniqid('name');
        $this->entity->setName($testData);
        $this->assertEquals($testData, $this->entity->getName());

        $testData = uniqid('definition');
        $this->entity->setDefinition($testData);
        $this->assertEquals($testData, $this->entity->getDefinition());

        $testData = uniqid('description');
        $this->entity->setDescription($testData);
        $this->assertEquals($testData, $this->entity->getDescription());

        $testData = $this->getMock('Oro\Bundle\SegmentBundle\Entity\SegmentType', [], ['testTypeName']);
        $this->entity->setType($testData);
        $this->assertSame($testData, $this->entity->getType());

        $testData = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\BusinessUnit');
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
    }
}
