<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;

class FiltersConfigExtraTest extends \PHPUnit_Framework_TestCase
{
    /** @var FiltersConfigExtra */
    protected $extra;

    protected function setUp()
    {
        $this->extra = new FiltersConfigExtra();
    }

    public function testGetName()
    {
        $this->assertEquals(FiltersConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable()
    {
        $this->assertTrue($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        $this->assertEquals(FiltersConfigExtra::NAME, $this->extra->getCacheKeyPart());
    }

    public function testConfigType()
    {
        $this->assertEquals('filters', $this->extra->getConfigType());
    }
}
