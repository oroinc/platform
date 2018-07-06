<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;

class FiltersConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    /** @var FiltersConfigExtra */
    private $extra;

    protected function setUp()
    {
        $this->extra = new FiltersConfigExtra();
    }

    public function testGetName()
    {
        self::assertEquals(FiltersConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable()
    {
        self::assertTrue($this->extra->isPropagable());
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
