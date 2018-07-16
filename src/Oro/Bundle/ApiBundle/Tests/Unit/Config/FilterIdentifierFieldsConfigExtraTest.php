<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;

class FilterIdentifierFieldsConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    /** @var FilterIdentifierFieldsConfigExtra */
    private $extra;

    protected function setUp()
    {
        $this->extra = new FilterIdentifierFieldsConfigExtra();
    }

    public function testGetName()
    {
        self::assertEquals(FilterIdentifierFieldsConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable()
    {
        self::assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        self::assertEquals(FilterIdentifierFieldsConfigExtra::NAME, $this->extra->getCacheKeyPart());
    }
}
