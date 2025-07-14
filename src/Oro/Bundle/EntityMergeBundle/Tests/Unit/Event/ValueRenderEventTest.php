<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Event;

use Oro\Bundle\EntityMergeBundle\Event\ValueRenderEvent;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValueRenderEventTest extends TestCase
{
    private MetadataInterface&MockObject $metadata;
    private \DateTime $originalValue;
    private string $convertedValue;
    private ValueRenderEvent $target;

    #[\Override]
    protected function setUp(): void
    {
        $this->metadata = $this->createMock(MetadataInterface::class);
        $this->originalValue = new \DateTime();
        $this->convertedValue = date('Y-m');

        $this->target = new ValueRenderEvent($this->convertedValue, $this->originalValue, $this->metadata);
    }

    public function testGetMetadataReturnsAnOriginalMetadata(): void
    {
        $this->assertEquals($this->target->getMetadata(), $this->metadata);
    }

    public function testGetOriginalValueReturnAnOriginalValue(): void
    {
        $this->assertEquals($this->target->getOriginalValue(), $this->originalValue);
    }

    public function testGetConvertedValueShouldReturnConvertedValue(): void
    {
        $this->assertEquals($this->target->getConvertedValue(), $this->convertedValue);
    }

    public function testSetConvertedValueShouldChangeConvertedValue(): void
    {
        $newConvertedValue = date('Y');

        $this->target->setConvertedValue($newConvertedValue);

        $this->assertEquals($this->target->getConvertedValue(), $newConvertedValue);
    }
}
