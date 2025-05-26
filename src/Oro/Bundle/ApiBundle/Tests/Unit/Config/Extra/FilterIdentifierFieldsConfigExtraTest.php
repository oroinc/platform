<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\FilterIdentifierFieldsConfigExtra;
use PHPUnit\Framework\TestCase;

class FilterIdentifierFieldsConfigExtraTest extends TestCase
{
    private FilterIdentifierFieldsConfigExtra $extra;

    #[\Override]
    protected function setUp(): void
    {
        $this->extra = new FilterIdentifierFieldsConfigExtra();
    }

    public function testGetName(): void
    {
        self::assertEquals(FilterIdentifierFieldsConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable(): void
    {
        self::assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart(): void
    {
        self::assertEquals(FilterIdentifierFieldsConfigExtra::NAME, $this->extra->getCacheKeyPart());
    }
}
