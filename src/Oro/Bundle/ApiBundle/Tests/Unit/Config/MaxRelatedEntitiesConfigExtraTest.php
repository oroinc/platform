<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\MaxRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

class MaxRelatedEntitiesConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    /** @var MaxRelatedEntitiesConfigExtra */
    private $extra;

    protected function setUp()
    {
        $this->extra = new MaxRelatedEntitiesConfigExtra(123);
    }

    public function testGetName()
    {
        self::assertEquals(MaxRelatedEntitiesConfigExtra::NAME, $this->extra->getName());
    }

    public function testGetMaxRelatedEntities()
    {
        self::assertEquals(
            123,
            $this->extra->getMaxRelatedEntities()
        );
    }

    public function testConfigureContext()
    {
        $context = new ConfigContext();
        $this->extra->configureContext($context);
        self::assertEquals(
            123,
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
