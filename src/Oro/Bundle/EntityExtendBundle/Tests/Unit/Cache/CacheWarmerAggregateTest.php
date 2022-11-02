<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Cache;

use Oro\Bundle\EntityBundle\Tools\CheckDatabaseStateManager;
use Oro\Bundle\EntityExtendBundle\Cache\CacheWarmerAggregate;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate as SymfonyCacheWarmerAggregate;

class CacheWarmerAggregateTest extends \PHPUnit\Framework\TestCase
{
    /** @var SymfonyCacheWarmerAggregate */
    private $cacheWarmer;

    /** @var SymfonyCacheWarmerAggregate */
    private $extendCacheWarmer;

    /** @var CacheWarmerAggregate */
    private $cacheWarmerAggregate;

    protected function setUp(): void
    {
        $this->cacheWarmer = $this->createMock(SymfonyCacheWarmerAggregate::class);
        $this->extendCacheWarmer = $this->createMock(SymfonyCacheWarmerAggregate::class);

        $cacheWarmerLink = $this->createMock(ServiceLink::class);
        $cacheWarmerLink->expects(self::any())
            ->method('getService')
            ->willReturn($this->cacheWarmer);
        $extendCacheWarmerLink = $this->createMock(ServiceLink::class);
        $extendCacheWarmerLink->expects(self::any())
            ->method('getService')
            ->willReturn($this->extendCacheWarmer);

        $this->cacheWarmerAggregate = new CacheWarmerAggregate(
            $cacheWarmerLink,
            $extendCacheWarmerLink,
            new CheckDatabaseStateManager([])
        );
    }

    public function testIsOptional()
    {
        self::assertFalse($this->cacheWarmerAggregate->isOptional());
    }

    public function testWarmUpWithoutOptionalWarmers()
    {
        $cacheDir = 'test';

        $this->cacheWarmer->expects(self::once())
            ->method('warmUp')
            ->with($cacheDir)
            ->willReturn([]);
        $this->cacheWarmer->expects(self::never())
            ->method('enableOnlyOptionalWarmers');
        $this->cacheWarmer->expects(self::never())
            ->method('enableOptionalWarmers');

        $this->extendCacheWarmer->expects(self::never())
            ->method('warmUp');
        $this->extendCacheWarmer->expects(self::never())
            ->method('enableOnlyOptionalWarmers');
        $this->extendCacheWarmer->expects(self::never())
            ->method('enableOptionalWarmers');

        $this->cacheWarmerAggregate->warmUp($cacheDir);
    }

    public function testWarmUpWithOptionalWarmers()
    {
        $cacheDir = 'test';

        $this->cacheWarmer->expects(self::once())
            ->method('warmUp')
            ->with($cacheDir)
            ->willReturn([]);
        $this->cacheWarmer->expects(self::never())
            ->method('enableOnlyOptionalWarmers');
        $this->cacheWarmer->expects(self::once())
            ->method('enableOptionalWarmers');

        $this->extendCacheWarmer->expects(self::never())
            ->method('warmUp');
        $this->extendCacheWarmer->expects(self::never())
            ->method('enableOnlyOptionalWarmers');
        $this->extendCacheWarmer->expects(self::never())
            ->method('enableOptionalWarmers');

        $this->cacheWarmerAggregate->enableOptionalWarmers();
        $this->cacheWarmerAggregate->warmUp($cacheDir);
    }

    public function testWarmUpWithOnlyOptionalWarmers()
    {
        $cacheDir = 'test';

        $this->cacheWarmer->expects(self::once())
            ->method('warmUp')
            ->with($cacheDir)
            ->willReturn([]);
        $this->cacheWarmer->expects(self::once())
            ->method('enableOnlyOptionalWarmers');
        $this->cacheWarmer->expects(self::never())
            ->method('enableOptionalWarmers');

        $this->extendCacheWarmer->expects(self::never())
            ->method('warmUp');
        $this->extendCacheWarmer->expects(self::never())
            ->method('enableOnlyOptionalWarmers');
        $this->extendCacheWarmer->expects(self::never())
            ->method('enableOptionalWarmers');

        $this->cacheWarmerAggregate->enableOnlyOptionalWarmers();
        $this->cacheWarmerAggregate->warmUp($cacheDir);
    }
}
