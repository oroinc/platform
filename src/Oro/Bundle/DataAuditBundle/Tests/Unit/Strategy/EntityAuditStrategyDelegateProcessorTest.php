<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Strategy;

use Oro\Bundle\DataAuditBundle\Strategy\EntityAuditStrategyDelegateProcessor;
use Oro\Bundle\DataAuditBundle\Strategy\EntityAuditStrategyProcessorRegistry;
use Oro\Bundle\DataAuditBundle\Strategy\Processor\EntityAuditStrategyProcessorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityAuditStrategyDelegateProcessorTest extends TestCase
{
    private EntityAuditStrategyProcessorRegistry|MockObject $registry;

    private EntityAuditStrategyProcessorInterface|MockObject $processor1;

    private EntityAuditStrategyProcessorInterface|MockObject $defaultProcessor;

    private EntityAuditStrategyDelegateProcessor $processor;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(EntityAuditStrategyProcessorRegistry::class);

        $this->processor = new EntityAuditStrategyDelegateProcessor($this->registry);
    }

    public function testProcessWithKnownEntity()
    {
        $sourceEntityData['entity_class'] = 'Test\Entity';
        $this->processor1 = $this->createMock(EntityAuditStrategyProcessorInterface::class);

        $this->registry->expects($this->once())
            ->method('hasProcessor')
            ->willReturn(true);
        $this->registry->expects($this->once())
            ->method('getProcessor')
            ->with('Test\Entity')
            ->willReturn($this->processor1);
        $this->processor1->expects($this->once())
            ->method('processInverseCollections')
            ->willReturn([]);

        $result = $this->processor->processInverseCollections($sourceEntityData);

        $this->assertEmpty($result);
    }

    public function testProcessWithUnKnownEntity(): void
    {
        $sourceEntityData['entity_class'] = 'Test\UnknownEntity';
        $this->defaultProcessor = $this->createMock(EntityAuditStrategyProcessorInterface::class);

        $this->registry->expects($this->once())
            ->method('hasProcessor')
            ->willReturn(false);
        $this->registry->expects($this->never())
            ->method('getProcessor');
        $this->registry->expects($this->atLeastOnce())
            ->method('getDefaultProcessor')
            ->willReturn($this->defaultProcessor);
        $this->defaultProcessor->expects($this->once())
            ->method('processInverseCollections')
            ->willReturn([]);

        $result = $this->processor->processInverseCollections($sourceEntityData);

        $this->assertEmpty($result);
    }
}
