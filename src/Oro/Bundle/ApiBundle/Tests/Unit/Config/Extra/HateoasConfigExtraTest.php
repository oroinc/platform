<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\HateoasConfigExtra;
use PHPUnit\Framework\TestCase;

class HateoasConfigExtraTest extends TestCase
{
    private HateoasConfigExtra $extra;

    #[\Override]
    protected function setUp(): void
    {
        $this->extra = new HateoasConfigExtra();
    }

    public function testGetName(): void
    {
        self::assertEquals(HateoasConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable(): void
    {
        self::assertTrue($this->extra->isPropagable());
    }

    public function testCacheKeyPart(): void
    {
        self::assertEquals(
            HateoasConfigExtra::NAME,
            $this->extra->getCacheKeyPart()
        );
    }
}
