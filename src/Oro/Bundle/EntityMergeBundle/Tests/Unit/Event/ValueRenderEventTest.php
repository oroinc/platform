<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Event;

use Oro\Bundle\EntityMergeBundle\Event\ValueRenderEvent;

class ValueRenderEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ValueRenderEvent
     */
    protected $target;

    /**
     * @var \DateTime
     */
    protected $originalValue;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $metadata;

    /**
     * @var string
     */
    protected $convertedValue;

    protected function setUp()
    {
        $this->metadata = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\MetadataInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->originalValue = new \DateTime();

        $this->convertedValue = date('Y-m');

        $this->target = new ValueRenderEvent($this->convertedValue, $this->originalValue, $this->metadata);
    }

    public function testGetMetadataReturnsAnOriginalMetadata()
    {
        $this->assertEquals($this->target->getMetadata(), $this->metadata);
    }

    public function testGetOriginalValueReturnAnOriginalValue()
    {
        $this->assertEquals($this->target->getOriginalValue(), $this->originalValue);
    }

    public function testGetConvertedValueShouldReturnConvertedValue()
    {
        $this->assertEquals($this->target->getConvertedValue(), $this->convertedValue);
    }

    public function testSetConvertedValueShouldChangeConvertedValue()
    {
        $newConvertedValue = date('Y');

        $this->target->setConvertedValue($newConvertedValue);

        $this->assertEquals($this->target->getConvertedValue(), $newConvertedValue);
    }
}
