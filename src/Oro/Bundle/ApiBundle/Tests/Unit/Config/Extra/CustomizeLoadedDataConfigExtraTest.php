<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\CustomizeLoadedDataConfigExtra;
use PHPUnit\Framework\TestCase;

class CustomizeLoadedDataConfigExtraTest extends TestCase
{
    private CustomizeLoadedDataConfigExtra $extra;

    #[\Override]
    protected function setUp(): void
    {
        $this->extra = new CustomizeLoadedDataConfigExtra();
    }

    public function testGetName(): void
    {
        self::assertEquals(CustomizeLoadedDataConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable(): void
    {
        self::assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart(): void
    {
        self::assertEquals(CustomizeLoadedDataConfigExtra::NAME, $this->extra->getCacheKeyPart());
    }
}
