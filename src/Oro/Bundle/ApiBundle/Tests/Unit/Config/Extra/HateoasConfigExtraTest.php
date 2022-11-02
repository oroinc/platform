<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\HateoasConfigExtra;

class HateoasConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    /** @var HateoasConfigExtra */
    private $extra;

    protected function setUp(): void
    {
        $this->extra = new HateoasConfigExtra();
    }

    public function testGetName()
    {
        self::assertEquals(HateoasConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable()
    {
        self::assertTrue($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        self::assertEquals(
            HateoasConfigExtra::NAME,
            $this->extra->getCacheKeyPart()
        );
    }
}
