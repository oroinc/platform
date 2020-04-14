<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\FilterIdentifierFieldsConfigExtra;

class FilterIdentifierFieldsConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    /** @var FilterIdentifierFieldsConfigExtra */
    private $extra;

    protected function setUp(): void
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
