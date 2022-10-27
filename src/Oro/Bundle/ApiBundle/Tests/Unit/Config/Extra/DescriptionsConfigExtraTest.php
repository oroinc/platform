<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\DescriptionsConfigExtra;

class DescriptionsConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    /** @var DescriptionsConfigExtra */
    private $extra;

    protected function setUp(): void
    {
        $this->extra = new DescriptionsConfigExtra();
    }

    public function testGetName()
    {
        self::assertEquals(DescriptionsConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable()
    {
        self::assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        self::assertEquals(DescriptionsConfigExtra::NAME, $this->extra->getCacheKeyPart());
    }
}
