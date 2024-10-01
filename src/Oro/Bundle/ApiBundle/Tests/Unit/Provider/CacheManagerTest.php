<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor;
use Oro\Bundle\ApiBundle\ApiDoc\Extractor\CachingApiDocExtractor;
use Oro\Bundle\ApiBundle\Provider\CacheManager;
use Oro\Bundle\ApiBundle\Provider\ConfigCacheWarmer;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\EntityAliasResolverRegistry;
use Oro\Bundle\ApiBundle\Provider\ResourcesCacheWarmer;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CacheManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigCacheWarmer */
    private $configCacheWarmer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityAliasResolverRegistry */
    private $entityAliasResolverRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ResourcesCacheWarmer */
    private $resourcesCacheWarmer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    private $configProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configCacheWarmer = $this->createMock(ConfigCacheWarmer::class);
        $this->entityAliasResolverRegistry = $this->createMock(EntityAliasResolverRegistry::class);
        $this->resourcesCacheWarmer = $this->createMock(ResourcesCacheWarmer::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
    }

    private function getCacheManager(
        array $apiDocViews,
        ApiDocExtractor $apiDocExtractor
    ): CacheManager {
        return new CacheManager(
            $apiDocViews,
            $this->configCacheWarmer,
            $this->entityAliasResolverRegistry,
            $this->resourcesCacheWarmer,
            $apiDocExtractor,
            $this->configProvider
        );
    }

    public function testClearCaches()
    {
        $cacheManager = $this->getCacheManager([], $this->createMock(ApiDocExtractor::class));

        $this->entityAliasResolverRegistry->expects(self::once())
            ->method('clearCache');
        $this->resourcesCacheWarmer->expects(self::once())
            ->method('clearCache');
        $this->configCacheWarmer->expects(self::never())
            ->method(self::anything());

        $cacheManager->clearCaches();
    }

    public function testWarmUpCaches()
    {
        $cacheManager = $this->getCacheManager([], $this->createMock(ApiDocExtractor::class));

        $this->entityAliasResolverRegistry->expects(self::once())
            ->method('warmUpCache');
        $this->resourcesCacheWarmer->expects(self::once())
            ->method('warmUpCache');
        $this->configCacheWarmer->expects(self::never())
            ->method(self::anything());

        $cacheManager->warmUpCaches();
    }

    public function testWarmUpConfigCache()
    {
        $cacheManager = $this->getCacheManager([], $this->createMock(ApiDocExtractor::class));

        $this->configCacheWarmer->expects(self::once())
            ->method('warmUpCache');
        $this->entityAliasResolverRegistry->expects(self::never())
            ->method(self::anything());
        $this->resourcesCacheWarmer->expects(self::never())
            ->method(self::anything());

        $cacheManager->warmUpConfigCache();
    }

    public function testIsApiDocCacheEnabledForNonCachingApiDocExtractor()
    {
        $cacheManager = $this->getCacheManager([], $this->createMock(ApiDocExtractor::class));

        self::assertFalse($cacheManager->isApiDocCacheEnabled());
    }

    public function testIsApiDocCacheEnabledForCachingApiDocExtractor()
    {
        $cacheManager = $this->getCacheManager([], $this->createMock(CachingApiDocExtractor::class));

        self::assertTrue($cacheManager->isApiDocCacheEnabled());
    }

    public function testClearApiDocCacheForNonCachingApiDocExtractor()
    {
        $apiDocExtractor = $this->createMock(ApiDocExtractor::class);
        $cacheManager = $this->getCacheManager(['view1' => ['rest'], 'view2' => []], $apiDocExtractor);

        $cacheManager->clearApiDocCache();
    }

    public function testClearApiDocCacheForCachingApiDocExtractorAndViewIsNotSpecified()
    {
        $apiDocExtractor = $this->createMock(CachingApiDocExtractor::class);
        $cacheManager = $this->getCacheManager(['view1' => ['rest'], 'view2' => []], $apiDocExtractor);

        $apiDocExtractor->expects(self::exactly(2))
            ->method('clear')
            ->withConsecutive(['view1'], ['view2']);

        $cacheManager->clearApiDocCache();
    }

    public function testClearApiDocCacheForCachingApiDocExtractorAndViewIsSpecified()
    {
        $apiDocExtractor = $this->createMock(CachingApiDocExtractor::class);
        $cacheManager = $this->getCacheManager(['view1' => ['rest'], 'view2' => []], $apiDocExtractor);

        $apiDocExtractor->expects(self::once())
            ->method('clear')
            ->with('view1');

        $cacheManager->clearApiDocCache('view1');
    }

    public function testWarmUpApiDocCacheForNonCachingApiDocExtractor()
    {
        $apiDocExtractor = $this->createMock(ApiDocExtractor::class);
        $cacheManager = $this->getCacheManager(['view1' => ['rest'], 'view2' => []], $apiDocExtractor);

        $cacheManager->warmUpApiDocCache();
    }

    public function testWarmUpApiDocCacheForCachingApiDocExtractorAndViewIsNotSpecified()
    {
        $apiDocExtractor = $this->createMock(CachingApiDocExtractor::class);
        $resettableService = $this->createMock(ResetInterface::class);
        $cacheManager = $this->getCacheManager(['view1' => ['rest'], 'view2' => []], $apiDocExtractor);
        $cacheManager->addResettableService($resettableService);

        $calls = [];
        $this->configProvider->expects(self::once())
            ->method('disableFullConfigsCache')
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'disableFullConfigsCache';
            });
        $this->configProvider->expects(self::once())
            ->method('enableFullConfigsCache')
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'enableFullConfigsCache';
            });
        $apiDocExtractor->expects(self::exactly(2))
            ->method('warmUp')
            ->withConsecutive(['view1'], ['view2'])
            ->willReturnCallback(function ($view) use (&$calls) {
                $calls[] = 'apiDocExtractor::warmUp - ' . $view;
            });
        $resettableService->expects(self::exactly(2))
            ->method('reset')
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'resettableService::reset';
            });

        $cacheManager->warmUpApiDocCache();

        self::assertEquals(
            [
                'disableFullConfigsCache',
                'apiDocExtractor::warmUp - view1',
                'resettableService::reset',
                'apiDocExtractor::warmUp - view2',
                'resettableService::reset',
                'enableFullConfigsCache'
            ],
            $calls
        );
    }

    public function testWarmUpApiDocCacheForCachingApiDocExtractorAndViewIsSpecified()
    {
        $apiDocExtractor = $this->createMock(CachingApiDocExtractor::class);
        $resettableService = $this->createMock(ResetInterface::class);
        $cacheManager = $this->getCacheManager(['view1' => ['rest'], 'view2' => []], $apiDocExtractor);
        $cacheManager->addResettableService($resettableService);

        $calls = [];
        $this->configProvider->expects(self::once())
            ->method('disableFullConfigsCache')
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'disableFullConfigsCache';
            });
        $this->configProvider->expects(self::once())
            ->method('enableFullConfigsCache')
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'enableFullConfigsCache';
            });
        $apiDocExtractor->expects(self::once())
            ->method('warmUp')
            ->with('view1')
            ->willReturnCallback(function ($view) use (&$calls) {
                $calls[] = 'apiDocExtractor::warmUp - ' . $view;
            });
        $resettableService->expects(self::once())
            ->method('reset')
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'resettableService::reset';
            });

        $cacheManager->warmUpApiDocCache('view1');

        self::assertEquals(
            [
                'disableFullConfigsCache',
                'apiDocExtractor::warmUp - view1',
                'resettableService::reset',
                'enableFullConfigsCache'
            ],
            $calls
        );
    }
}
