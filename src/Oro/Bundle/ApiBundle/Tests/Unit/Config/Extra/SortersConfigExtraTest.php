<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\SortersConfigExtra;
use PHPUnit\Framework\TestCase;

class SortersConfigExtraTest extends TestCase
{
    private SortersConfigExtra $extra;

    #[\Override]
    protected function setUp(): void
    {
        $this->extra = new SortersConfigExtra();
    }

    public function testGetName(): void
    {
        self::assertEquals(SortersConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable(): void
    {
        self::assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart(): void
    {
        self::assertEquals(SortersConfigExtra::NAME, $this->extra->getCacheKeyPart());
    }

    public function testConfigType(): void
    {
        self::assertEquals('sorters', $this->extra->getConfigType());
    }
}
