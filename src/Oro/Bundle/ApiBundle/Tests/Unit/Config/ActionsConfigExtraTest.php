<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\ActionsConfigExtra;

class ActionsConfigExtraTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActionsConfigExtra */
    protected $extra;

    protected function setUp()
    {
        $this->extra = new ActionsConfigExtra();
    }

    public function testGetName()
    {
        $this->assertEquals(ActionsConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable()
    {
        $this->assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        $this->assertEquals(ActionsConfigExtra::NAME, $this->extra->getCacheKeyPart());
    }

    public function testConfigType()
    {
        $this->assertEquals(ActionsConfigExtra::NAME, $this->extra->getConfigType());
    }
}
