<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use PHPUnit\Framework\TestCase;

class ExpandRelatedEntitiesConfigExtraTest extends TestCase
{
    private const array EXPANDED_ENTITIES = ['product', 'product.owner', 'category.owner', 'category'];

    private ExpandRelatedEntitiesConfigExtra $extra;

    #[\Override]
    protected function setUp(): void
    {
        $this->extra = new ExpandRelatedEntitiesConfigExtra(self::EXPANDED_ENTITIES);
    }

    public function testGetName(): void
    {
        self::assertEquals(ExpandRelatedEntitiesConfigExtra::NAME, $this->extra->getName());
    }

    public function testGetExpandedEntities(): void
    {
        self::assertEquals(
            self::EXPANDED_ENTITIES,
            $this->extra->getExpandedEntities()
        );
    }

    public function testIsExpandRequested(): void
    {
        self::assertTrue($this->extra->isExpandRequested('product'));
        self::assertTrue($this->extra->isExpandRequested('product.owner'));
        self::assertTrue($this->extra->isExpandRequested('category'));
        self::assertTrue($this->extra->isExpandRequested('category.owner'));
        self::assertFalse($this->extra->isExpandRequested('another'));
        self::assertFalse($this->extra->isExpandRequested('owner'));
        self::assertFalse($this->extra->isExpandRequested('product.another'));
        self::assertFalse($this->extra->isExpandRequested('product.owner.another'));
    }

    public function testConfigureContext(): void
    {
        $context = new ConfigContext();
        $this->extra->configureContext($context);
        self::assertEquals(
            self::EXPANDED_ENTITIES,
            $context->get(ExpandRelatedEntitiesConfigExtra::NAME)
        );
    }

    public function testIsPropagable(): void
    {
        self::assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart(): void
    {
        self::assertEquals(
            'expand:product,product.owner,category.owner,category',
            $this->extra->getCacheKeyPart()
        );
    }
}
