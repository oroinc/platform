<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

class ExpandRelatedEntitiesConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExpandRelatedEntitiesConfigExtra */
    private $extra;

    protected function setUp()
    {
        $this->extra = new ExpandRelatedEntitiesConfigExtra(['products', 'categories']);
    }

    public function testGetName()
    {
        self::assertEquals(ExpandRelatedEntitiesConfigExtra::NAME, $this->extra->getName());
    }

    public function testGetExpandedEntities()
    {
        self::assertEquals(
            ['products', 'categories'],
            $this->extra->getExpandedEntities()
        );
    }

    public function testConfigureContext()
    {
        $context = new ConfigContext();
        $this->extra->configureContext($context);
        self::assertEquals(
            ['products', 'categories'],
            $context->get(ExpandRelatedEntitiesConfigExtra::NAME)
        );
    }

    public function testIsPropagable()
    {
        self::assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        self::assertEquals(
            'expand:products,categories',
            $this->extra->getCacheKeyPart()
        );
    }
}
