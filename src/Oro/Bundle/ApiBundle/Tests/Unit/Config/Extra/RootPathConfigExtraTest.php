<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\RootPathConfigExtra;
use PHPUnit\Framework\TestCase;

class RootPathConfigExtraTest extends TestCase
{
    private const string PATH = 'test';

    private RootPathConfigExtra $extra;

    #[\Override]
    protected function setUp(): void
    {
        $this->extra = new RootPathConfigExtra(self::PATH);
    }

    public function testGetName(): void
    {
        self::assertEquals(RootPathConfigExtra::NAME, $this->extra->getName());
    }

    public function testGetExpandedEntitiesPath(): void
    {
        self::assertEquals(self::PATH, $this->extra->getPath());
    }

    public function testIsPropagable(): void
    {
        self::assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart(): void
    {
        self::assertEquals('path:test', $this->extra->getCacheKeyPart());
    }
}
