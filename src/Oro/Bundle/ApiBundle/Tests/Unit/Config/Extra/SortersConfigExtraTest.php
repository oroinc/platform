<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\SortersConfigExtra;

class SortersConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    /** @var SortersConfigExtra */
    private $extra;

    protected function setUp(): void
    {
        $this->extra = new SortersConfigExtra();
    }

    public function testGetName()
    {
        self::assertEquals(SortersConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable()
    {
        self::assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        self::assertEquals(SortersConfigExtra::NAME, $this->extra->getCacheKeyPart());
    }

    public function testConfigType()
    {
        self::assertEquals('sorters', $this->extra->getConfigType());
    }
}
