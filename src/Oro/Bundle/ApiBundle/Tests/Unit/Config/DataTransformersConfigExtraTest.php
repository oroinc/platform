<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\DataTransformersConfigExtra;

class DataTransformersConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    /** @var DataTransformersConfigExtra */
    private $extra;

    protected function setUp()
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
