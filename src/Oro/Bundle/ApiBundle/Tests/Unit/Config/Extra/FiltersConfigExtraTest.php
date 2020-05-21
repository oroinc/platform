<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\FiltersConfigExtra;

class FiltersConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    /** @var FiltersConfigExtra */
    private $extra;

    protected function setUp(): void
    {
        $this->extra = new FiltersConfigExtra();
    }

    public function testGetName()
    {
        self::assertEquals(FiltersConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable()
    {
        self::assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        self::assertEquals(FiltersConfigExtra::NAME, $this->extra->getCacheKeyPart());
    }

    public function testConfigType()
    {
        self::assertEquals('filters', $this->extra->getConfigType());
    }
}
