<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ResourcesCache;
use Oro\Bundle\ApiBundle\Provider\ResourcesCacheAccessor;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ResourcesCacheTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|CacheItemPoolInterface */
    private $cache;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CacheItemInterface */
    private $cacheItem;

    /** @var ResourcesCache */
    private $resourcesCache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->cacheItem = $this->createMock(CacheItemInterface::class);

        $this->resourcesCache = new ResourcesCache(
            new ResourcesCacheAccessor($this->cache)
        );
    }

    public function testGetAccessibleResourcesNoCache()
    {
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with('accessible_1.2rest')
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);

        self::assertNull(
            $this->resourcesCache->getAccessibleResources('1.2', new RequestType(['rest']))
        );
    }

    public function testGetAccessibleResources()
    {
        $cachedData = [null, ['Test\Entity1' => 0, 'Test\Entity2' => 1, 'Test\Entity3' => 3]];

        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects(self::once())
            ->method('get')
            ->willReturn($cachedData);
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with('accessible_1.2rest')
            ->willReturn($this->cacheItem);

        self::assertEquals(
            $cachedData[1],
            $this->resourcesCache->getAccessibleResources('1.2', new RequestType(['rest']))
        );
    }

    public function testGetExcludedActionsNoCache()
    {
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with('excluded_actions_1.2rest')
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);

        self::assertNull(
            $this->resourcesCache->getExcludedActions('1.2', new RequestType(['rest']))
        );
    }

    public function testGetExcludedActions()
    {
        $cachedData = [null, ['Test\Entity1' => ['delete']]];

        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects(self::once())
            ->method('get')
            ->willReturn($cachedData);
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with('excluded_actions_1.2rest')
            ->willReturn($this->cacheItem);

        self::assertEquals(
            $cachedData[1],
            $this->resourcesCache->getExcludedActions('1.2', new RequestType(['rest']))
        );
    }

    public function testGetResourcesNoCache()
    {
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with('resources_1.2rest')
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);

        self::assertNull(
            $this->resourcesCache->getResources('1.2', new RequestType(['rest']))
        );
    }

    public function testGetResources()
    {
        $cachedData = [null, ['Test\Entity1' => [[]], 'Test\Entity2' => [['create']]]];

        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects(self::once())
            ->method('get')
            ->willReturn($cachedData);
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with('resources_1.2rest')
            ->willReturn($this->cacheItem);

        $resource1 = new ApiResource('Test\Entity1');
        $resource2 = new ApiResource('Test\Entity2');
        $resource2->setExcludedActions(['create']);
        self::assertEquals(
            [$resource1, $resource2],
            $this->resourcesCache->getResources('1.2', new RequestType(['rest']))
        );
    }

    public function testGetResourcesWithoutIdentifierNoCache()
    {
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with('resources_wid_1.2rest')
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);

        self::assertNull(
            $this->resourcesCache->getResourcesWithoutIdentifier('1.2', new RequestType(['rest']))
        );
    }

    public function testGetResourcesWithoutIdentifier()
    {
        $cachedData = [null, ['Test\Entity1', 'Test\Entity2']];

        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects(self::once())
            ->method('get')
            ->willReturn($cachedData);
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with('resources_wid_1.2rest')
            ->willReturn($this->cacheItem);

        self::assertEquals(
            $cachedData[1],
            $this->resourcesCache->getResourcesWithoutIdentifier('1.2', new RequestType(['rest']))
        );
    }

    public function testSave()
    {
        $resource1 = new ApiResource('Test\Entity1');
        $resource2 = new ApiResource('Test\Entity2');
        $resource2->setExcludedActions(['get', 'get_list']);
        $resource3 = new ApiResource('Test\Entity3');
        $resource3->setExcludedActions(['create']);

        $accessibleResources = [
            'Test\Entity1' => 0,
            'Test\Entity2' => 1,
            'Test\Entity3' => 3
        ];
        $excludedActions = [
            'Test\Entity2' => ['get', 'get_list'],
            'Test\Entity3' => ['create']
        ];

        $cacheItem1 = $this->createMock(CacheItemInterface::class);
        $cacheItem2 = $this->createMock(CacheItemInterface::class);
        $cacheItem3 = $this->createMock(CacheItemInterface::class);
        $this->cache->expects(self::exactly(3))
            ->method('getItem')
            ->withConsecutive(['resources_1.2rest'], ['accessible_1.2rest'], ['excluded_actions_1.2rest'])
            ->willReturnOnConsecutiveCalls($cacheItem1, $cacheItem2, $cacheItem3);
        $cacheItem1->expects(self::once())
            ->method('set')
            ->with([null, [
                'Test\Entity1' => [[]],
                'Test\Entity2' => [['get', 'get_list']],
                'Test\Entity3' => [['create']]
            ]]);
        $cacheItem2->expects(self::once())
            ->method('set')
            ->with([null, $accessibleResources]);
        $cacheItem3->expects(self::once())
            ->method('set')
            ->with([null, $excludedActions]);
        $this->cache->expects(self::exactly(3))
            ->method('save')
            ->withConsecutive([$cacheItem1], [$cacheItem2], [$cacheItem3]);

        $this->resourcesCache->saveResources(
            '1.2',
            new RequestType(['rest']),
            [$resource1, $resource2, $resource3],
            $accessibleResources,
            $excludedActions
        );
    }

    public function testSaveResourcesWithoutIdentifier()
    {
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with('resources_wid_1.2rest')
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('set')
            ->with([null, ['Test\Entity1', 'Test\Entity2']]);
        $this->cache->expects(self::once())
            ->method('save')
            ->with($this->cacheItem);

        $this->resourcesCache->saveResourcesWithoutIdentifier(
            '1.2',
            new RequestType(['rest']),
            ['Test\Entity1', 'Test\Entity2']
        );
    }

    public function testClear()
    {
        $this->cache->expects(self::once())
            ->method('clear');

        $this->resourcesCache->clear();
    }
}
