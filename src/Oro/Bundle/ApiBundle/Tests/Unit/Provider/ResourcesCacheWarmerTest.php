<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ResourcesCacheWarmer;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Provider\SubresourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ResourcesCacheWarmerTest extends TestCase
{
    private ResourcesProvider&MockObject $resourcesProvider;
    private SubresourcesProvider&MockObject $subresourcesProvider;
    private ResourcesCacheWarmer $cacheWarmer;

    #[\Override]
    protected function setUp(): void
    {
        $this->resourcesProvider = $this->createMock(ResourcesProvider::class);
        $this->subresourcesProvider = $this->createMock(SubresourcesProvider::class);

        $this->cacheWarmer = new ResourcesCacheWarmer(
            $this->resourcesProvider,
            $this->subresourcesProvider,
            [['rest']]
        );
    }

    public function testWarmUp(): void
    {
        $version = Version::LATEST;
        $requestType = new RequestType(['rest']);
        $this->resourcesProvider->expects(self::once())
            ->method('clearCache');
        $this->resourcesProvider->expects(self::once())
            ->method('getResources')
            ->with($version, $requestType)
            ->willReturn([new ApiResource('Test\Entity1')]);
        $this->subresourcesProvider->expects(self::once())
            ->method('getSubresources')
            ->with('Test\Entity1', $version, $requestType);

        $this->cacheWarmer->warmUp('test');
    }

    public function testIsOptional(): void
    {
        self::assertTrue($this->cacheWarmer->isOptional());
    }

    public function testWarmUpCache(): void
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

    public function testClearCache(): void
    {
        $this->resourcesProvider->expects(self::once())
            ->method('clearCache');

        $this->cacheWarmer->clearCache();
    }
}
