<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\DataTransformersConfigExtra;
use PHPUnit\Framework\TestCase;

class DataTransformersConfigExtraTest extends TestCase
{
    private DataTransformersConfigExtra $extra;

    #[\Override]
    protected function setUp(): void
    {
        $this->extra = new DataTransformersConfigExtra();
    }

    public function testGetName(): void
    {
        self::assertEquals(DataTransformersConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable(): void
    {
        self::assertTrue($this->extra->isPropagable());
    }

    public function testCacheKeyPart(): void
    {
        self::assertEquals(DataTransformersConfigExtra::NAME, $this->extra->getCacheKeyPart());
    }
}
