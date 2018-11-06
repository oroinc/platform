<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ResourcesCacheWarmer;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Provider\SubresourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Version;

class ResourcesCacheWarmerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ResourcesProvider */
    private $resourcesProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|SubresourcesProvider */
    private $subresourcesProvider;

    /** @var ResourcesCacheWarmer */
    private $cacheWarmer;

    protected function setUp()
    {
        $this->resourcesProvider = $this->createMock(ResourcesProvider::class);
        $this->subresourcesProvider = $this->createMock(SubresourcesProvider::class);

        $this->cacheWarmer = new ResourcesCacheWarmer(
            $this->resourcesProvider,
            $this->subresourcesProvider,
            [['rest']]
        );
    }

    public function testWarmUp()
    {
        $version = Version::LATEST;
        $requestType = new RequestType(['rest']);
        $this->resourcesProvider->expects(self::once())
            ->method('getResources')
            ->with($version, $requestType)
            ->willReturn([new ApiResource('Test\Entity1')]);
        $this->subresourcesProvider->expects(self::once())
            ->method('getSubresources')
            ->with('Test\Entity1', $version, $requestType);

        $this->cacheWarmer->warmUp('test');
    }

    public function testIsOptional()
    {
        self::assertTrue($this->cacheWarmer->isOptional());
    }

    public function testWarmUpCache()
    {
        $version = Version::LATEST;
        $requestType = new RequestType(['rest']);
        $this->resourcesProvider->expects(self::once())
            ->method('getResources')
            ->with($version, $requestType)
            ->willReturn([new ApiResource('Test\Entity1')]);
        $this->subresourcesProvider->expects(self::once())
            ->method('getSubresources')
            ->with('Test\Entity1', $version, $requestType);

        $this->cacheWarmer->warmUpCache();
    }

    public function testClearCache()
    {
        $this->resourcesProvider->expects(self::once())
            ->method('clearCache');

        $this->cacheWarmer->clearCache();
    }
}
