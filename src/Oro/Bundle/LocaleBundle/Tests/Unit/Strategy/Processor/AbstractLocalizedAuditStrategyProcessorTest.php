<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Strategy\Processor;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataAuditBundle\Strategy\Processor\EntityAuditStrategyProcessorInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Strategy\Processor\AbstractLocalizedAuditStrategyProcessor;
use PHPUnit\Framework\TestCase;

class AbstractLocalizedAuditStrategyProcessorTest extends TestCase
{
    private EntityAuditStrategyProcessorInterface $strategyProcessor;

    protected function setUp(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);

        $this->strategyProcessor = new AbstractLocalizedAuditStrategyProcessor($doctrine);
    }

    public function testProcessChangedEntities(): void
    {
        $sourceEntityData = [
            'entity_class' => LocalizedFallbackValue::class,
            'entity_id' => 234
        ];

        $result = $this->strategyProcessor->processChangedEntities($sourceEntityData);
        $this->assertEmpty($result);
    }

    public function testProcessInverseRelations(): void
    {
        $sourceEntityData = [
            'entity_class' => LocalizedFallbackValue::class,
            'entity_id' => 345
        ];

        $result = $this->strategyProcessor->processInverseRelations($sourceEntityData);
        $this->assertEmpty($result);
    }
}
