<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\RootPathConfigExtra;

class RootPathConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    private const PATH = 'test';

    /** @var RootPathConfigExtra */
    private $extra;

    protected function setUp(): void
    {
        $this->extra = new RootPathConfigExtra(self::PATH);
    }

    public function testGetName()
    {
        self::assertEquals(RootPathConfigExtra::NAME, $this->extra->getName());
    }

    public function testGetExpandedEntitiesPath()
    {
        self::assertEquals(self::PATH, $this->extra->getPath());
    }

    public function testIsPropagable()
    {
        self::assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        self::assertEquals('path:test', $this->extra->getCacheKeyPart());
    }
}
