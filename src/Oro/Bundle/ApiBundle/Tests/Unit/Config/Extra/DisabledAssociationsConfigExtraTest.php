<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\DisabledAssociationsConfigExtra;

class DisabledAssociationsConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    private DisabledAssociationsConfigExtra $extra;

    protected function setUp(): void
    {
        $this->extra = new DisabledAssociationsConfigExtra();
    }

    public function testGetName(): void
    {
        self::assertEquals(DisabledAssociationsConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable(): void
    {
        self::assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart(): void
    {
        self::assertEquals(DisabledAssociationsConfigExtra::NAME, $this->extra->getCacheKeyPart());
    }
}
