<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\DescriptionsConfigExtra;

class DescriptionsConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    /** @var DescriptionsConfigExtra */
    private $extra;

    protected function setUp()
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
