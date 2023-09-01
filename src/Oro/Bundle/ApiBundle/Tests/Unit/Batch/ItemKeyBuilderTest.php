<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch;

use Oro\Bundle\ApiBundle\Batch\ItemKeyBuilder;

class ItemKeyBuilderTest extends \PHPUnit\Framework\TestCase
{
    private ItemKeyBuilder $itemKeyBuilder;

    protected function setUp(): void
    {
        $this->itemKeyBuilder = new ItemKeyBuilder();
    }

    public function testBuildItemKey(): void
    {
        self::assertEquals('type|id', $this->itemKeyBuilder->buildItemKey('type', 'id'));
    }
}
