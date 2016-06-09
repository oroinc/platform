<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ResourcesCache;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;

class ResourcesCacheTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $cache;

    /** @var ResourcesCache */
    protected $resourcesCache;

    protected function setUp()
    {
        $this->cache = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->setMethods(['fetch', 'save', 'deleteAll'])
            ->getMockForAbstractClass();

        $this->resourcesCache = new ResourcesCache($this->cache);
    }

    public function testGetAccessibleResourcesNoCache()
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('accessible_1.2rest')
            ->willReturn(false);

        $this->assertNull(
            $this->resourcesCache->getAccessibleResources('1.2', new RequestType(['rest']))
        );
    }

    public function testGetAccessibleResources()
    {
        $cachedData = ['Test\Entity1', 'Test\Entity2'];

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('accessible_1.2rest')
            ->willReturn($cachedData);

        $this->assertEquals(
            $cachedData,
            $this->resourcesCache->getAccessibleResources('1.2', new RequestType(['rest']))
        );
    }

    public function testGetResourcesNoCache()
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('resources_1.2rest')
            ->willReturn(false);

        $this->assertNull(
            $this->resourcesCache->getResources('1.2', new RequestType(['rest']))
        );
    }

    public function testGetResources()
    {
        $cachedData = [
            'Test\Entity1' => [[]],
            'Test\Entity2' => [['create']]
        ];

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('resources_1.2rest')
            ->willReturn($cachedData);

        $resource1 = new ApiResource('Test\Entity1');
        $resource2 = new ApiResource('Test\Entity2');
        $resource2->setExcludedActions(['create']);
        $this->assertEquals(
            [$resource1, $resource2],
            $this->resourcesCache->getResources('1.2', new RequestType(['rest']))
        );
    }

    public function testSave()
    {
        $resource1 = new ApiResource('Test\Entity1');
        $resource2 = new ApiResource('Test\Entity2');
        $resource2->setExcludedActions(['get', 'get_list']);
        $resource3 = new ApiResource('Test\Entity3');
        $resource3->setExcludedActions(['create']);

        $this->cache->expects($this->at(0))
            ->method('save')
            ->with(
                'resources_1.2rest',
                [
                    'Test\Entity1' => [[]],
                    'Test\Entity2' => [['get', 'get_list']],
                    'Test\Entity3' => [['create']]
                ]
            );
        $this->cache->expects($this->at(1))
            ->method('save')
            ->with(
                'accessible_1.2rest',
                ['Test\Entity1', 'Test\Entity3']
            );

        $this->resourcesCache->save(
            '1.2',
            new RequestType(['rest']),
            [$resource1, $resource2, $resource3]
        );
    }

    public function testClear()
    {
        $this->cache->expects($this->once())
            ->method('deleteAll');

        $this->resourcesCache->clear();
    }
}
