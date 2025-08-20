<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Provider;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\SearchBundle\Configuration\MappingConfigurationProvider;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;
use Oro\Bundle\SearchBundle\Provider\SearchMappingCacheNormalizer;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SearchMappingProviderTest extends TestCase
{
    private array $testMapping = [
        'Oro\TestBundle\Entity\TestEntity' => [
            'alias' => 'test_entity',
            'fields' => [
                [
                    'name' => 'firstname',
                    'target_type' => 'text',
                    'target_columns' => ['firstname']
                ],
                [
                    'name' => 'qty',
                    'target_type' => 'integer',
                    'target_columns' => ['qty']
                ]
            ]
        ]
    ];

    private CacheItemPoolInterface&MockObject $cache;
    private CacheItemInterface&MockObject $cacheItem;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private MappingConfigurationProvider&MockObject $configProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->cacheItem = $this->createMock(CacheItemInterface::class);
        $this->configProvider = $this->createMock(MappingConfigurationProvider::class);

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($this->testMapping);
    }

    public function testGetEntitiesListAliases(): void
    {
        self::assertEquals(
            ['Oro\TestBundle\Entity\TestEntity' => 'test_entity'],
            $this->getProvider()->getEntitiesListAliases()
        );
    }

    public function testGetEntityAliases(): void
    {
        self::assertEquals(
            ['Oro\TestBundle\Entity\TestEntity' => 'test_entity'],
            $this->getProvider()->getEntityAliases(['Oro\TestBundle\Entity\TestEntity'])
        );
    }

    public function testGetEntityAliasesForUnknownEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The search alias for the entity "Oro\TestBundle\Entity\UnknownEntity" not found.'
        );

        $this->getProvider()->getEntityAliases(
            ['Oro\TestBundle\Entity\TestEntity', 'Oro\TestBundle\Entity\UnknownEntity']
        );
    }

    public function testGetEntityAliasesForEmptyClassNames(): void
    {
        self::assertEquals(
            ['Oro\TestBundle\Entity\TestEntity' => 'test_entity'],
            $this->getProvider()->getEntityAliases()
        );
    }

    public function testGetEntityAlias(): void
    {
        self::assertEquals(
            'test_entity',
            $this->getProvider()->getEntityAlias('Oro\TestBundle\Entity\TestEntity')
        );
    }

    public function testGetEntityAliasForUnknownEntity(): void
    {
        self::assertNull(
            $this->getProvider()->getEntityAlias('Oro\TestBundle\Entity\UnknownEntity')
        );
    }

    public function testGetEntityClasses(): void
    {
        self::assertEquals(
            ['Oro\TestBundle\Entity\TestEntity'],
            $this->getProvider()->getEntityClasses()
        );
    }

    public function testIsClassSupported(): void
    {
        $provider = $this->getProvider();

        self::assertTrue($provider->isClassSupported('Oro\TestBundle\Entity\TestEntity'));
        self::assertFalse($provider->isClassSupported('Oro\TestBundle\Entity\BadEntity'));
    }

    public function testHasFieldsMapping(): void
    {
        $provider = $this->getProvider();

        self::assertTrue($provider->hasFieldsMapping('Oro\TestBundle\Entity\TestEntity'));
        self::assertFalse($provider->hasFieldsMapping('Oro\TestBundle\Entity\BadEntity'));
    }

    public function testGetEntityMapParameter(): void
    {
        $provider = $this->getProvider();

        self::assertEquals(
            'test_entity',
            $provider->getEntityMapParameter('Oro\TestBundle\Entity\TestEntity', 'alias')
        );
        self::assertFalse(
            $provider->getEntityMapParameter('Oro\TestBundle\Entity\TestEntity', 'badParameter')
        );
    }

    public function testGetEntityClass(): void
    {
        self::assertEquals(
            'Oro\TestBundle\Entity\TestEntity',
            $this->getProvider()->getEntityClass('test_entity')
        );
    }

    public function testGetEntityClassForUnknownAlias(): void
    {
        self::assertNull(
            $this->getProvider()->getEntityClass('unknown_entity')
        );
    }

    public function testGetMappingConfigCached(): void
    {
        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        self::assertEquals($this->testMapping, $this->getProvider()->getMappingConfig());
    }

    public function testGetMappingConfigWhenConfigurationNotLoaded(): void
    {
        $configTimestamp = 20;

        $this->configProvider->expects(self::never())
            ->method('isCacheFresh');
        $this->configProvider->expects(self::once())
            ->method('getCacheTimestamp')
            ->willReturn($configTimestamp);

        $this->cache->expects(self::once())
            ->method('getItem')
            ->with(UniversalCacheKeyGenerator::normalizeCacheKey('oro_search.mapping_config:search_engine'))
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);
        $this->cacheItem->expects(self::once())
            ->method('set')
            ->with(
                [$configTimestamp, []]
            )
            ->willReturn($this->cacheItem);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (SearchMappingCollectEvent $event, $eventName) {
                self::assertEquals('oro_search.search_mapping_collect', $eventName);
                self::assertEquals($this->testMapping, $event->getMappingConfig());

                $event->setMappingConfig([]);

                return $event;
            });

        $this->cache->expects(self::once())
            ->method('save')
            ->with($this->cacheItem);

        $provider = $this->getProvider(false);
        self::assertEquals([], $provider->getMappingConfig());
        self::assertEquals([], $provider->getMappingConfig());
    }

    public function testGetMappingConfigWhenConfigurationChanged(): void
    {
        $cacheTimestamp = 10;
        $configTimestamp = 20;

        $this->configProvider->expects(self::once())
            ->method('isCacheFresh')
            ->with($cacheTimestamp)
            ->willReturn(false);
        $this->configProvider->expects(self::once())
            ->method('getCacheTimestamp')
            ->willReturn($configTimestamp);

        $this->cache->expects(self::once())
            ->method('getItem')
            ->with(UniversalCacheKeyGenerator::normalizeCacheKey('oro_search.mapping_config:search_engine'))
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects(self::once())
            ->method('get')
            ->willReturn([$cacheTimestamp, []]);
        $this->cacheItem->expects(self::once())
            ->method('set')
            ->with([$configTimestamp, []])
            ->willReturn($this->cacheItem);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (SearchMappingCollectEvent $event, $eventName) {
                self::assertEquals('oro_search.search_mapping_collect', $eventName);
                self::assertEquals($this->testMapping, $event->getMappingConfig());

                $event->setMappingConfig([]);

                return $event;
            });

        $this->cache->expects(self::once())
            ->method('save')
            ->with($this->cacheItem);

        $provider = $this->getProvider(false);
        self::assertEquals([], $provider->getMappingConfig());
        self::assertEquals([], $provider->getMappingConfig());
    }

    public function testGetMappingConfigWhenConfigurationNotChanged(): void
    {
        $cacheTimestamp = 10;

        $this->configProvider->expects(self::once())
            ->method('isCacheFresh')
            ->with($cacheTimestamp)
            ->willReturn(true);
        $this->configProvider->expects(self::never())
            ->method('getCacheTimestamp');

        $this->cache->expects(self::once())
            ->method('getItem')
            ->with(UniversalCacheKeyGenerator::normalizeCacheKey('oro_search.mapping_config:search_engine'))
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects(self::once())
            ->method('get')
            ->willReturn([$cacheTimestamp, []]);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $this->cache->expects(self::never())
            ->method('save');

        $provider = $this->getProvider(false);
        self::assertEquals([], $provider->getMappingConfig());
        self::assertEquals([], $provider->getMappingConfig());
    }

    public function testClearCache(): void
    {
        $cacheKey = UniversalCacheKeyGenerator::normalizeCacheKey('oro_search.mapping_config:search_engine');
        $this->cache->expects(self::once())
            ->method('deleteItem')
            ->with($cacheKey);

        $this->cache->expects(self::once())
            ->method('getItem')
            ->with($cacheKey)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);
        $this->cacheItem->expects(self::once())
            ->method('set')
            ->willReturn($this->cacheItem);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (SearchMappingCollectEvent $event) {
                $event->setMappingConfig($this->testMapping);

                return $event;
            });
        $this->configProvider->expects(self::never())
            ->method('clearCache');
        $this->configProvider->expects(self::never())
            ->method('warmUpCache');

        $provider = $this->getProvider(false);
        $provider->clearCache();
        self::assertEquals($this->testMapping, $provider->getMappingConfig());
    }

    public function testWarmUpCache(): void
    {
        $cacheKey = UniversalCacheKeyGenerator::normalizeCacheKey('oro_search.mapping_config:search_engine');
        $this->cache->expects(self::once())
            ->method('deleteItem')
            ->with($cacheKey);

        $this->cache->expects(self::once())
            ->method('getItem')
            ->with($cacheKey)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);
        $this->cacheItem->expects(self::once())
            ->method('set')
            ->willReturn($this->cacheItem);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                self::logicalAnd(
                    self::isInstanceOf(SearchMappingCollectEvent::class),
                    self::callback(function (SearchMappingCollectEvent $event) {
                        return self::equalTo($this->testMapping)->evaluate($event->getMappingConfig());
                    })
                ),
                'oro_search.search_mapping_collect'
            )
            ->willReturnCallback(function (SearchMappingCollectEvent $event) {
                $event->setMappingConfig($this->testMapping);

                return $event;
            });

        $provider = $this->getProvider(false);
        $provider->warmUpCache();

        self::assertEquals($this->testMapping, $provider->getMappingConfig());
    }

    private function getProvider(bool $mockFetch = true): SearchMappingProvider
    {
        $provider = new SearchMappingProvider(
            $this->eventDispatcher,
            $this->configProvider,
            $this->cache,
            new SearchMappingCacheNormalizer([], [], 'target_type'),
            'oro_search.search_mapping_collect',
            'oro_search.mapping_config',
            'search_engine'
        );
        if ($mockFetch) {
            $this->configProvider->expects(self::once())
                ->method('isCacheFresh')
                ->with(self::isNull())
                ->willReturn(true);
            $this->cache->expects(self::once())
                ->method('getItem')
                ->with(UniversalCacheKeyGenerator::normalizeCacheKey('oro_search.mapping_config:search_engine'))
                ->willReturn($this->cacheItem);
            $this->cacheItem->expects(self::once())
                ->method('isHit')
                ->willReturn(true);
            $this->cacheItem->expects(self::once())
                ->method('get')
                ->willReturn([null, $this->testMapping]);
        }

        return $provider;
    }
}
