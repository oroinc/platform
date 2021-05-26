<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Strategy;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\DefaultAccessor;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;
use Oro\Bundle\EntityMergeBundle\Model\Strategy\ReplaceStrategy;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;

class ReplaceStrategyTest extends \PHPUnit\Framework\TestCase
{
    /** @var ReplaceStrategy */
    private $strategy;

    protected function setUp(): void
    {
        $this->strategy = new ReplaceStrategy(new DefaultAccessor());
    }

    public function testNotSupports()
    {
        $fieldData = $this->createMock(FieldData::class);
        $fieldData->expects($this->once())
            ->method('getMode')
            ->willReturn(MergeModes::UNITE);

        $this->assertFalse($this->strategy->supports($fieldData));
    }

    public function testSupports()
    {
        $fieldData = $this->createMock(FieldData::class);
        $fieldData->expects($this->once())
            ->method('getMode')
            ->willReturn(MergeModes::REPLACE);

        $this->assertTrue($this->strategy->supports($fieldData));
    }

    public function testMerge()
    {
        $masterEntity = new EntityStub(1);
        $sourceEntity = new EntityStub(2);

        $entityData = $this->createMock(EntityData::class);
        $fieldData = $this->createMock(FieldData::class);
        $fieldMetadataData = $this->createMock(FieldMetadata::class);

        $entityData->expects($this->once())
            ->method('getMasterEntity')
            ->willReturn($masterEntity);

        $fieldData->expects($this->once())
            ->method('getEntityData')
            ->willReturn($entityData);
        $fieldData->expects($this->once())
            ->method('getMetadata')
            ->willReturn($fieldMetadataData);
        $fieldData->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn($sourceEntity);

        $fieldMetadataData->expects($this->atLeastOnce())
            ->method('has')
            ->willReturn(true);
        $fieldMetadataData->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls('getId', 'setId');

        $this->strategy->merge($fieldData);

        $this->assertEquals($sourceEntity->getId(), $masterEntity->getId());
    }

    public function testGetName()
    {
        $this->assertEquals('replace', $this->strategy->getName());
    }
}
