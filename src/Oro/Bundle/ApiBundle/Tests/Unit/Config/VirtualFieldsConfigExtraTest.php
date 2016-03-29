<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\VirtualFieldsConfigExtra;

class VirtualFieldsConfigExtraTest extends \PHPUnit_Framework_TestCase
{
    /** @var VirtualFieldsConfigExtra */
    protected $extra;

    protected function setUp()
    {
        $this->extra = new VirtualFieldsConfigExtra();
    }

    public function testGetName()
    {
        $this->assertEquals(VirtualFieldsConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable()
    {
        $this->assertTrue($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        $this->assertEquals(VirtualFieldsConfigExtra::NAME, $this->extra->getCacheKeyPart());
    }
}
