<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\FiltersConfigExtra;
use PHPUnit\Framework\TestCase;

class FiltersConfigExtraTest extends TestCase
{
    private FiltersConfigExtra $extra;

    #[\Override]
    protected function setUp(): void
    {
        $this->extra = new FiltersConfigExtra();
    }

    public function testGetName(): void
    {
        self::assertEquals(FiltersConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable(): void
    {
        self::assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart(): void
    {
        self::assertEquals(FiltersConfigExtra::NAME, $this->extra->getCacheKeyPart());
    }

    public function testConfigType(): void
    {
        self::assertEquals('filters', $this->extra->getConfigType());
    }
}
