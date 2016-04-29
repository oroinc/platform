<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\SortersConfigExtra;

class SortersConfigExtraTest extends \PHPUnit_Framework_TestCase
{
    /** @var SortersConfigExtra */
    protected $extra;

    protected function setUp()
    {
        $this->extra = new SortersConfigExtra();
    }

    public function testGetName()
    {
        $this->assertEquals(SortersConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable()
    {
        $this->assertTrue($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        $this->assertEquals(SortersConfigExtra::NAME, $this->extra->getCacheKeyPart());
    }

    public function testConfigType()
    {
        $this->assertEquals('sorters', $this->extra->getConfigType());
    }
}
