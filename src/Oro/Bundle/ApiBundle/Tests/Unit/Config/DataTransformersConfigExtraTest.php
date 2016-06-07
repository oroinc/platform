<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\DataTransformersConfigExtra;

class DataTransformersConfigExtraTest extends \PHPUnit_Framework_TestCase
{
    /** @var DataTransformersConfigExtra */
    protected $extra;

    protected function setUp()
    {
        $this->extra = new DataTransformersConfigExtra();
    }

    public function testGetName()
    {
        $this->assertEquals(DataTransformersConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable()
    {
        $this->assertTrue($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        $this->assertEquals(DataTransformersConfigExtra::NAME, $this->extra->getCacheKeyPart());
    }
}
