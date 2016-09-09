<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\DescriptionsConfigExtra;

class DescriptionsConfigExtraTest extends \PHPUnit_Framework_TestCase
{
    /** @var DescriptionsConfigExtra */
    protected $extra;

    protected function setUp()
    {
        $this->extra = new DescriptionsConfigExtra();
    }

    public function testGetName()
    {
        $this->assertEquals(DescriptionsConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable()
    {
        $this->assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        $this->assertEquals(DescriptionsConfigExtra::NAME, $this->extra->getCacheKeyPart());
    }
}
