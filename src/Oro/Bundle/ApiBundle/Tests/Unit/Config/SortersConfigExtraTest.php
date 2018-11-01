<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\SortersConfigExtra;

class SortersConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    /** @var SortersConfigExtra */
    private $extra;

    protected function setUp()
    {
        $this->extra = new SortersConfigExtra();
    }

    public function testGetName()
    {
        self::assertEquals(SortersConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable()
    {
        self::assertTrue($this->extra->isPropagable());
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
