<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\MaxRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;

class MaxRelatedEntitiesConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    private const MAX_RELATED_ENTITIES = 123;

    /** @var MaxRelatedEntitiesConfigExtra */
    private $extra;

    protected function setUp(): void
    {
        $this->extra = new MaxRelatedEntitiesConfigExtra(self::MAX_RELATED_ENTITIES);
    }

    public function testGetName()
    {
        self::assertEquals(MaxRelatedEntitiesConfigExtra::NAME, $this->extra->getName());
    }

    public function testGetMaxRelatedEntities()
    {
        self::assertEquals(
            self::MAX_RELATED_ENTITIES,
            $this->extra->getMaxRelatedEntities()
        );
    }

    public function testConfigureContext()
    {
        $context = new ConfigContext();
        $this->extra->configureContext($context);
        self::assertEquals(
            self::MAX_RELATED_ENTITIES,
            $context->getMaxRelatedEntities()
        );
    }

    public function testIsPropagable()
    {
        self::assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        self::assertEquals(
            'max_related_entities:123',
            $this->extra->getCacheKeyPart()
        );
    }
}
