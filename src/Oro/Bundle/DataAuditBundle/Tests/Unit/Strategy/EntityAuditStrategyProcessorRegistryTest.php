<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Strategy;

use Oro\Bundle\DataAuditBundle\Strategy\EntityAuditStrategyProcessorRegistry;
use Oro\Bundle\DataAuditBundle\Strategy\Processor\DefaultEntityAuditStrategyProcessor;
use Oro\Bundle\DataAuditBundle\Strategy\Processor\EntityAuditStrategyProcessorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityAuditStrategyProcessorRegistryTest extends TestCase
{
    private EntityAuditStrategyProcessorInterface|MockObject $processor1;

    private DefaultEntityAuditStrategyProcessor|MockObject $defaultProcessor;

    private EntityAuditStrategyProcessorRegistry $registry;

    protected function setUp(): void
    {
        $this->processor1 = $this->createMock(EntityAuditStrategyProcessorInterface::class);
        $this->defaultProcessor = $this->createMock(DefaultEntityAuditStrategyProcessor::class);

        $this->registry = new EntityAuditStrategyProcessorRegistry($this->defaultProcessor);
        $this->registry->addProcessor($this->processor1, 'Test\Entity');
    }

    public function testHasProcessorAndGetForKnownEntity(): void
    {
        self::assertTrue($this->registry->hasProcessor('Test\Entity'));
        self::assertSame($this->processor1, $this->registry->getProcessor('Test\Entity'));
    }

    public function testHasProcessorForUnknownEntity(): void
    {
        self::assertFalse($this->registry->hasProcessor('Test\UnknownEntity'));
    }

    public function testGetDefaultProcessor(): void
    {
        self::assertEquals($this->defaultProcessor, $this->registry->getDefaultProcessor());
    }

    public function testAddProcessorForDuplicatedProcessor(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'You should not override an existed strategy processor for entity "%s".',
            'Test\Entity'
        ));

        $processor2 = $this->createMock(EntityAuditStrategyProcessorInterface::class);

        $this->registry->addProcessor($this->processor1, 'Test\Entity');
        $this->registry->addProcessor($processor2, 'Test\Entity');
    }
}
