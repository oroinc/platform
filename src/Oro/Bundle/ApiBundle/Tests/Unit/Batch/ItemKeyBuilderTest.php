<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch;

use Oro\Bundle\ApiBundle\Batch\ItemKeyBuilder;
use PHPUnit\Framework\TestCase;

class ItemKeyBuilderTest extends TestCase
{
    private ItemKeyBuilder $itemKeyBuilder;

    #[\Override]
    protected function setUp(): void
    {
        $this->itemKeyBuilder = new ItemKeyBuilder();
    }

    public function testBuildItemKey(): void
    {
        self::assertEquals('type|id', $this->itemKeyBuilder->buildItemKey('type', 'id'));
    }
}
