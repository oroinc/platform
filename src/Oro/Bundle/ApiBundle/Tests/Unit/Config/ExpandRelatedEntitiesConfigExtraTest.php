<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

class ExpandRelatedEntitiesConfigExtraTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExpandRelatedEntitiesConfigExtra */
    protected $extra;

    protected function setUp()
    {
        $this->extra = new ExpandRelatedEntitiesConfigExtra(['products', 'categories']);
    }

    public function testGetName()
    {
        $this->assertEquals(ExpandRelatedEntitiesConfigExtra::NAME, $this->extra->getName());
    }

    public function testConfigureContext()
    {
        $context = new ConfigContext();
        $this->extra->configureContext($context);
        $this->assertEquals(
            ['products', 'categories'],
            $context->get(ExpandRelatedEntitiesConfigExtra::NAME)
        );
    }

    public function testIsPropagable()
    {
        $this->assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        $this->assertEquals(
            'expand:products,categories',
            $this->extra->getCacheKeyPart()
        );
    }
}
