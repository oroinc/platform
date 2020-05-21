<?php

namespace Oro\Component\Config\Tests\Unit\Cache;

use Oro\Component\Config\Cache\ConfigCacheWarmer;
use Oro\Component\Config\Cache\WarmableConfigCacheInterface;

class ConfigCacheWarmerTest extends \PHPUnit\Framework\TestCase
{
    /** @var WarmableConfigCacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(WarmableConfigCacheInterface::class);
    }

    public function testShouldBeOptionalByDefault()
    {
        $warmer = new ConfigCacheWarmer($this->cache);
        self::assertTrue($warmer->isOptional());
    }

    public function testShouldBePossibleToCreateNotOptional()
    {
        $warmer = new ConfigCacheWarmer($this->cache, false);
        self::assertFalse($warmer->isOptional());
    }

    public function testWarmUp()
    {
        $warmer = new ConfigCacheWarmer($this->cache);
        $this->cache->expects(self::once())
            ->method('warmUpCache');
        $warmer->warmUp('');
    }
}
