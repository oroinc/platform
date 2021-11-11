<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Event;

use Oro\Bundle\EntityMergeBundle\Event\ValueRenderEvent;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataInterface;

class ValueRenderEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var MetadataInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $metadata;

    /** @var \DateTime */
    private $originalValue;

    /** @var string */
    private $convertedValue;

    /** @var ValueRenderEvent */
    private $target;

    protected function setUp(): void
    {
        $this->metadata = $this->createMock(MetadataInterface::class);
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
