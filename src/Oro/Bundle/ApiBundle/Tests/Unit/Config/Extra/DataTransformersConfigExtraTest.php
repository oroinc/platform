<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\DataTransformersConfigExtra;

class DataTransformersConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    /** @var DataTransformersConfigExtra */
    private $extra;

    protected function setUp(): void
    {
        $this->extra = new DataTransformersConfigExtra();
    }

    public function testGetName()
    {
        self::assertEquals(DataTransformersConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable()
    {
        self::assertTrue($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        self::assertEquals(DataTransformersConfigExtra::NAME, $this->extra->getCacheKeyPart());
    }
}
