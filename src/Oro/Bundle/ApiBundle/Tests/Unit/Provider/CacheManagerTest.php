<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor;
use Oro\Bundle\ApiBundle\ApiDoc\Extractor\CachingApiDocExtractor;
use Oro\Bundle\ApiBundle\Provider\CacheManager;
use Oro\Bundle\ApiBundle\Provider\ConfigCacheFactory;
use Oro\Bundle\ApiBundle\Provider\ConfigCacheWarmer;
use Oro\Bundle\ApiBundle\Provider\EntityAliasCacheWarmer;
use Oro\Bundle\ApiBundle\Provider\ResourcesCacheWarmer;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Symfony\Component\Config\ConfigCacheInterface;

class CacheManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigCacheFactory */
    private $configCacheFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigCacheWarmer */
    private $configCacheWarmer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityAliasCacheWarmer */
    private $entityAliasCacheWarmer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ResourcesCacheWarmer */
    private $resourcesCacheWarmer;

    protected function setUp()
    {
        $this->configCacheFactory = $this->createMock(ConfigCacheFactory::class);
        $this->configCacheWarmer = $this->createMock(ConfigCacheWarmer::class);
        $this->entityAliasCacheWarmer = $this->createMock(EntityAliasCacheWarmer::class);
        $this->resourcesCacheWarmer = $this->createMock(ResourcesCacheWarmer::class);
    }

    /**
     * @param array           $configKeys
     * @param array           $apiDocViews
     * @param ApiDocExtractor $apiDocExtractor
     *
     * @return CacheManager
     */
    private function getCacheManager(array $configKeys, array $apiDocViews, ApiDocExtractor $apiDocExtractor)
    {
        return new CacheManager(
            $configKeys,
            $apiDocViews,
            new RequestExpressionMatcher(),
            $this->configCacheFactory,
            $this->configCacheWarmer,
            $this->entityAliasCacheWarmer,
            $this->resourcesCacheWarmer,
            $apiDocExtractor
        );
    }

    public function testClearCaches()
    {
        $cacheManager = $this->getCacheManager([], [], $this->createMock(ApiDocExtractor::class));

        $this->configCacheWarmer->expects(self::once())
            ->method('warmUp');
        $this->entityAliasCacheWarmer->expects(self::once())
            ->method('clearCache');
        $this->resourcesCacheWarmer->expects(self::once())
            ->method('clearCache');

        $cacheManager->clearCaches();
    }

    public function testWarmUpCaches()
    {
        $cacheManager = $this->getCacheManager([], [], $this->createMock(ApiDocExtractor::class));

        $this->configCacheWarmer->expects(self::once())
            ->method('warmUp');
        $this->entityAliasCacheWarmer->expects(self::once())
            ->method('warmUpCache');
        $this->resourcesCacheWarmer->expects(self::once())
            ->method('warmUpCache');

        $cacheManager->warmUpCaches();
    }

    public function testWarmUpDirtyCachesWhenAllConfigCachesAreFresh()
    {
        $apiDocExtractor = $this->createMock(CachingApiDocExtractor::class);
        $cacheManager = $this->getCacheManager(
            [
                'api1' => ['rest'],
                'api2' => ['json_api', 'rest'],
                'api3' => []
            ],
            [
                'view1' => ['rest'],
                'view2' => ['json_api', 'rest'],
                'view3' => ['json_api', 'another'],
                'view4' => []
            ],
            $apiDocExtractor
        );

        $configCache1 = $this->createMock(ConfigCacheInterface::class);
        $configCache2 = $this->createMock(ConfigCacheInterface::class);
        $configCache3 = $this->createMock(ConfigCacheInterface::class);

        $this->configCacheFactory->expects(self::exactly(3))
            ->method('getCache')
            ->willReturnMap([
                ['api1', $configCache1],
                ['api2', $configCache2],
                ['api3', $configCache3]
            ]);
        $configCache1->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $configCache2->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);
        $configCache3->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);

        $this->configCacheWarmer->expects(self::never())
            ->method('warmUp');
        $this->entityAliasCacheWarmer->expects(self::never())
            ->method('warmUpCache');
        $this->resourcesCacheWarmer->expects(self::never())
            ->method('warmUpCache');
        $apiDocExtractor->expects(self::never())
            ->method('warmUp');

        $cacheManager->warmUpDirtyCaches();
    }

    /**
     * @dataProvider dirtyConfigCacheDataProvider
     */
    public function testWarmUpDirtyCachesWhenThereIsDirtyConfigCache($dirtyConfigKey, $toClearApiDocViews)
    {
        $apiDocExtractor = $this->createMock(CachingApiDocExtractor::class);
        $cacheManager = $this->getCacheManager(
            [
                'api1' => ['rest'],
                'api2' => ['json_api', 'rest'],
                'api3' => []
            ],
            [
                'view1' => ['rest'],
                'view2' => ['json_api', 'rest'],
                'view3' => ['json_api', 'another'],
                'view4' => []
            ],
            $apiDocExtractor
        );

        $configCaches = [
            'api1' => $this->createMock(ConfigCacheInterface::class),
            'api2' => $this->createMock(ConfigCacheInterface::class),
            'api3' => $this->createMock(ConfigCacheInterface::class)
        ];

        $this->configCacheFactory->expects(self::exactly(3))
            ->method('getCache')
            ->willReturnMap([
                ['api1', $configCaches['api1']],
                ['api2', $configCaches['api2']],
                ['api3', $configCaches['api3']]
            ]);
        foreach ($configCaches as $key => $configCache) {
            $configCache->expects(self::once())
                ->method('isFresh')
                ->willReturn($key !== $dirtyConfigKey);
        }

        $this->configCacheWarmer->expects(self::once())
            ->method('warmUp')
            ->with($dirtyConfigKey);
        $this->entityAliasCacheWarmer->expects(self::once())
            ->method('warmUpCache');
        $this->resourcesCacheWarmer->expects(self::once())
            ->method('warmUpCache');
        $clearedApiDocViews = [];
        $apiDocExtractor->expects(self::any())
            ->method('clear')
            ->willReturnCallback(function ($view) use (&$clearedApiDocViews) {
                $clearedApiDocViews[] = $view;
            });

        $cacheManager->warmUpDirtyCaches();
        self::assertEquals($toClearApiDocViews, $clearedApiDocViews);
    }

    public function dirtyConfigCacheDataProvider()
    {
        return [
            ['api1', ['view1', 'view2', 'view4']],
            ['api2', ['view2', 'view4']],
            ['api3', ['view1', 'view2', 'view3', 'view4']]
        ];
    }

    public function testWarmUpDirtyCachesWhenAllConfigCachesAreDirty()
    {
        $apiDocExtractor = $this->createMock(CachingApiDocExtractor::class);
        $cacheManager = $this->getCacheManager(
            [
                'api1' => ['rest'],
                'api2' => ['json_api', 'rest'],
                'api3' => []
            ],
            [
                'view1' => ['rest'],
                'view2' => ['json_api', 'rest'],
                'view3' => ['json_api', 'another'],
                'view4' => []
            ],
            $apiDocExtractor
        );

        $configCache1 = $this->createMock(ConfigCacheInterface::class);
        $configCache2 = $this->createMock(ConfigCacheInterface::class);
        $configCache3 = $this->createMock(ConfigCacheInterface::class);

        $this->configCacheFactory->expects(self::exactly(3))
            ->method('getCache')
            ->willReturnMap([
                ['api1', $configCache1],
                ['api2', $configCache2],
                ['api3', $configCache3]
            ]);
        $configCache1->expects(self::once())
            ->method('isFresh')
            ->willReturn(false);
        $configCache2->expects(self::once())
            ->method('isFresh')
            ->willReturn(false);
        $configCache3->expects(self::once())
            ->method('isFresh')
            ->willReturn(false);

        $this->configCacheWarmer->expects(self::exactly(3))
            ->method('warmUp')
            ->withConsecutive(['api1'], ['api2'], ['api3']);
        $this->entityAliasCacheWarmer->expects(self::once())
            ->method('warmUpCache');
        $this->resourcesCacheWarmer->expects(self::once())
            ->method('warmUpCache');
        $apiDocExtractor->expects(self::exactly(4))
            ->method('clear')
            ->withConsecutive(['view1'], ['view2'], ['view4'], ['view3']);

        $cacheManager->warmUpDirtyCaches();
    }

    public function testIsApiDocCacheEnabledForNonCachingApiDocExtractor()
    {
        $cacheManager = $this->getCacheManager([], [], $this->createMock(ApiDocExtractor::class));

        self::assertFalse($cacheManager->isApiDocCacheEnabled());
    }

    public function testIsApiDocCacheEnabledForCachingApiDocExtractor()
    {
        $cacheManager = $this->getCacheManager([], [], $this->createMock(CachingApiDocExtractor::class));

        self::assertTrue($cacheManager->isApiDocCacheEnabled());
    }

    public function testClearApiDocCacheForNonCachingApiDocExtractor()
    {
        $apiDocExtractor = $this->createMock(ApiDocExtractor::class);
        $cacheManager = $this->getCacheManager([], ['view1' => ['rest'], 'view2' => []], $apiDocExtractor);

        $cacheManager->clearApiDocCache();
    }

    public function testClearApiDocCacheForCachingApiDocExtractorAndViewIsNotSpecified()
    {
        $apiDocExtractor = $this->createMock(CachingApiDocExtractor::class);
        $cacheManager = $this->getCacheManager([], ['view1' => ['rest'], 'view2' => []], $apiDocExtractor);

        $apiDocExtractor->expects(self::exactly(2))
            ->method('clear')
            ->withConsecutive(['view1'], ['view2']);

        $cacheManager->clearApiDocCache();
    }

    public function testClearApiDocCacheForCachingApiDocExtractorAndViewIsSpecified()
    {
        $apiDocExtractor = $this->createMock(CachingApiDocExtractor::class);
        $cacheManager = $this->getCacheManager([], ['view1' => ['rest'], 'view2' => []], $apiDocExtractor);

        $apiDocExtractor->expects(self::once())
            ->method('clear')
            ->with('view1');

        $cacheManager->clearApiDocCache('view1');
    }

    public function testWarmUpApiDocCacheForNonCachingApiDocExtractor()
    {
        $apiDocExtractor = $this->createMock(ApiDocExtractor::class);
        $cacheManager = $this->getCacheManager([], ['view1' => ['rest'], 'view2' => []], $apiDocExtractor);

        $cacheManager->warmUpApiDocCache();
    }

    public function testWarmUpApiDocCacheForCachingApiDocExtractorAndViewIsNotSpecified()
    {
        $apiDocExtractor = $this->createMock(CachingApiDocExtractor::class);
        $cacheManager = $this->getCacheManager([], ['view1' => ['rest'], 'view2' => []], $apiDocExtractor);

        $apiDocExtractor->expects(self::exactly(2))
            ->method('warmUp')
            ->withConsecutive(['view1'], ['view2']);

        $cacheManager->warmUpApiDocCache();
    }

    public function testWarmUpApiDocCacheForCachingApiDocExtractorAndViewIsSpecified()
    {
        $apiDocExtractor = $this->createMock(CachingApiDocExtractor::class);
        $cacheManager = $this->getCacheManager([], ['view1' => ['rest'], 'view2' => []], $apiDocExtractor);

        $apiDocExtractor->expects(self::once())
            ->method('warmUp')
            ->with('view1');

        $cacheManager->warmUpApiDocCache('view1');
    }
}
