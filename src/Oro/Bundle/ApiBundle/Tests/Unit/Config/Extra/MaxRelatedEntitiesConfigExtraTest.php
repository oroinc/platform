<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\MaxRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use PHPUnit\Framework\TestCase;

class MaxRelatedEntitiesConfigExtraTest extends TestCase
{
    private const int MAX_RELATED_ENTITIES = 123;

    private MaxRelatedEntitiesConfigExtra $extra;

    #[\Override]
    protected function setUp(): void
    {
        $this->extra = new MaxRelatedEntitiesConfigExtra(self::MAX_RELATED_ENTITIES);
    }

    public function testGetName(): void
    {
        self::assertEquals(MaxRelatedEntitiesConfigExtra::NAME, $this->extra->getName());
    }

    public function testGetMaxRelatedEntities(): void
    {
        self::assertEquals(
            self::MAX_RELATED_ENTITIES,
            $this->extra->getMaxRelatedEntities()
        );
    }

    public function testConfigureContext(): void
    {
        $context = new ConfigContext();
        $this->extra->configureContext($context);
        self::assertEquals(
            self::MAX_RELATED_ENTITIES,
            $context->getMaxRelatedEntities()
        );
    }

    public function testIsPropagable(): void
    {
        self::assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart(): void
    {
        self::assertEquals(
            'max_related_entities:123',
            $this->extra->getCacheKeyPart()
        );
    }
}
