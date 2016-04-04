<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\CustomizeLoadedDataExtra;

class CustomizeLoadedDataExtraTest extends \PHPUnit_Framework_TestCase
{
    /** @var CustomizeLoadedDataExtra */
    protected $extra;

    protected function setUp()
    {
        $this->extra = new CustomizeLoadedDataExtra();
    }

    public function testGetName()
    {
        $this->assertEquals(CustomizeLoadedDataExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable()
    {
        $this->assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        $this->assertEquals(CustomizeLoadedDataExtra::NAME, $this->extra->getCacheKeyPart());
    }
}
