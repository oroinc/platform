<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\MaxRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

class MaxRelatedEntitiesConfigExtraTest extends \PHPUnit_Framework_TestCase
{
    /** @var MaxRelatedEntitiesConfigExtra */
    protected $extra;

    protected function setUp()
    {
        $this->extra = new MaxRelatedEntitiesConfigExtra(123);
    }

    public function testGetName()
    {
        $this->assertEquals(MaxRelatedEntitiesConfigExtra::NAME, $this->extra->getName());
    }

    public function testConfigureContext()
    {
        $context = new ConfigContext();
        $this->extra->configureContext($context);
        $this->assertEquals(
            123,
            $context->getMaxRelatedEntities()
        );
    }

    public function testIsPropagable()
    {
        $this->assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        $this->assertEquals(
            'max_related_entities:123',
            $this->extra->getCacheKeyPart()
        );
    }
}
