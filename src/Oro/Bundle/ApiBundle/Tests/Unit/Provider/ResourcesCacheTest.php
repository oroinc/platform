<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\ApiBundle\Provider\ResourcesCache;
use Oro\Bundle\ApiBundle\Provider\ResourcesCacheAccessor;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ResourcesCacheTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|CacheProvider */
    private $cache;

    /** @var ResourcesCache */
    private $resourcesCache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheProvider::class);

        $this->resourcesCache = new ResourcesCache(
            new ResourcesCacheAccessor($this->cache)
        );
    }

    public function testGetAccessibleResourcesNoCache()
    {
        $this->cache->expects(self::once())
            ->method('fetch')
            ->with('accessible_1.2rest')
            ->willReturn(false);

        self::assertNull(
            $this->resourcesCache->getAccessibleResources('1.2', new RequestType(['rest']))
        );
    }

    public function testGetAccessibleResources()
    {
        $cachedData = [null, ['Test\Entity1' => 0, 'Test\Entity2' => 1, 'Test\Entity3' => 3]];

        $this->cache->expects(self::once())
            ->method('fetch')
            ->with('accessible_1.2rest')
            ->willReturn($cachedData);

        self::assertEquals(
            $cachedData[1],
            $this->resourcesCache->getAccessibleResources('1.2', new RequestType(['rest']))
        );
    }

    public function testGetExcludedActionsNoCache()
    {
        $this->cache->expects(self::once())
            ->method('fetch')
            ->with('excluded_actions_1.2rest')
            ->willReturn(false);

        self::assertNull(
            $this->resourcesCache->getExcludedActions('1.2', new RequestType(['rest']))
        );
    }

    public function testGetExcludedActions()
    {
        $cachedData = [null, ['Test\Entity1' => ['delete']]];

        $this->cache->expects(self::once())
            ->method('fetch')
            ->with('excluded_actions_1.2rest')
            ->willReturn($cachedData);

        self::assertEquals(
            $cachedData[1],
            $this->resourcesCache->getExcludedActions('1.2', new RequestType(['rest']))
        );
    }

    public function testGetResourcesNoCache()
    {
        $this->cache->expects(self::once())
            ->method('fetch')
            ->with('resources_1.2rest')
            ->willReturn(false);

        self::assertNull(
            $this->resourcesCache->getResources('1.2', new RequestType(['rest']))
        );
    }

    public function testGetResources()
    {
        $cachedData = [null, ['Test\Entity1' => [[]], 'Test\Entity2' => [['create']]]];

        $this->cache->expects(self::once())
            ->method('fetch')
            ->with('resources_1.2rest')
            ->willReturn($cachedData);

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
            ->method('fetch')
            ->with('resources_wid_1.2rest')
            ->willReturn(false);

        self::assertNull(
            $this->resourcesCache->getResourcesWithoutIdentifier('1.2', new RequestType(['rest']))
        );
    }

    public function testGetResourcesWithoutIdentifier()
    {
        $cachedData = [null, ['Test\Entity1', 'Test\Entity2']];

        $this->cache->expects(self::once())
            ->method('fetch')
            ->with('resources_wid_1.2rest')
            ->willReturn($cachedData);

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

        $this->cache->expects(self::at(0))
            ->method('save')
            ->with(
                'resources_1.2rest',
                [
                    null,
                    [
                        'Test\Entity1' => [[]],
                        'Test\Entity2' => [['get', 'get_list']],
                        'Test\Entity3' => [['create']]
                    ]
                ]
            );
        $this->cache->expects(self::at(1))
            ->method('save')
            ->with(
                'accessible_1.2rest',
                [null, $accessibleResources]
            );
        $this->cache->expects(self::at(2))
            ->method('save')
            ->with(
                'excluded_actions_1.2rest',
                [null, $excludedActions]
            );

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
            ->method('save')
            ->with(
                'resources_wid_1.2rest',
                [null, ['Test\Entity1', 'Test\Entity2']]
            );

        $this->resourcesCache->saveResourcesWithoutIdentifier(
            '1.2',
            new RequestType(['rest']),
            ['Test\Entity1', 'Test\Entity2']
        );
    }

    public function testClear()
    {
        $this->cache->expects(self::once())
            ->method('deleteAll');

        $this->resourcesCache->clear();
    }
}
