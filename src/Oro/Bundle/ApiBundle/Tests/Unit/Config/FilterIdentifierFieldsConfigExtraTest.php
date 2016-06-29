<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;

class FilterIdentifierFieldsConfigExtraTest extends \PHPUnit_Framework_TestCase
{
    /** @var FilterIdentifierFieldsConfigExtra */
    protected $extra;

    protected function setUp()
    {
        $this->extra = new FilterIdentifierFieldsConfigExtra();
    }

    public function testGetName()
    {
        $this->assertEquals(FilterIdentifierFieldsConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable()
    {
        $this->assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        $this->assertEquals(FilterIdentifierFieldsConfigExtra::NAME, $this->extra->getCacheKeyPart());
    }
}
