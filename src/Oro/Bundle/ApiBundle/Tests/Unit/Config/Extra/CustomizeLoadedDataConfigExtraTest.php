<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\CustomizeLoadedDataConfigExtra;

class CustomizeLoadedDataConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    /** @var CustomizeLoadedDataConfigExtra */
    private $extra;

    protected function setUp(): void
    {
        $this->extra = new CustomizeLoadedDataConfigExtra();
    }

    public function testGetName()
    {
        self::assertEquals(CustomizeLoadedDataConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable()
    {
        self::assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        self::assertEquals(CustomizeLoadedDataConfigExtra::NAME, $this->extra->getCacheKeyPart());
    }
}
