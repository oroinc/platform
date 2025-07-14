<?php

namespace Oro\Component\Config\Tests\Unit\Cache;

use Oro\Component\Config\Cache\ConfigCacheWarmer;
use Oro\Component\Config\Cache\WarmableConfigCacheInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigCacheWarmerTest extends TestCase
{
    private WarmableConfigCacheInterface&MockObject $cache;

    #[\Override]
    protected function setUp(): void
    {
        $this->cache = $this->createMock(WarmableConfigCacheInterface::class);
    }

    public function testShouldBeOptionalByDefault(): void
    {
        $warmer = new ConfigCacheWarmer($this->cache);
        self::assertTrue($warmer->isOptional());
    }

    public function testShouldBePossibleToCreateNotOptional(): void
    {
        $warmer = new ConfigCacheWarmer($this->cache, false);
        self::assertFalse($warmer->isOptional());
    }

    public function testWarmUp(): void
    {
        $warmer = new ConfigCacheWarmer($this->cache);
        $this->cache->expects(self::once())
            ->method('warmUpCache');
        $warmer->warmUp('');
    }
}
