<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\CustomizeLoadedDataConfigExtra;

class CustomizeLoadedDataConfigExtraTest extends \PHPUnit_Framework_TestCase
{
    /** @var CustomizeLoadedDataConfigExtra */
    protected $extra;

    protected function setUp()
    {
        $this->extra = new CustomizeLoadedDataConfigExtra();
    }

    public function testGetName()
    {
        $this->assertEquals(CustomizeLoadedDataConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable()
    {
        $this->assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        $this->assertEquals(CustomizeLoadedDataConfigExtra::NAME, $this->extra->getCacheKeyPart());
    }
}
