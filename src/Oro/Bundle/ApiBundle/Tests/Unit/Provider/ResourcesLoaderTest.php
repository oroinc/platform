<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Provider\ResourcesLoader;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;

class ResourcesLoaderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $resourcesCache;

    /** @var ResourcesLoader */
    protected $loader;

    protected function setUp()
    {
        $this->processor = $this->getMockBuilder('Oro\Bundle\ApiBundle\Processor\CollectResourcesProcessor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourcesCache = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ResourcesCache')
            ->disableOriginalConstructor()
            ->getMock();

        $this->loader = new ResourcesLoader($this->processor, $this->resourcesCache);
    }

    public function testGetResourcesNoCache()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $expectedResources = [
            new ApiResource('Test\Entity1'),
            new ApiResource('Test\Entity3'),
        ];

        $this->processor->expects($this->once())
            ->method('process')
            ->willReturnCallback(
                function (CollectResourcesContext $context) use ($version, $requestType) {
                    $this->assertEquals($version, $context->getVersion());
                    $this->assertEquals($requestType, $context->getRequestType());

                    $context->getResult()->add(new ApiResource('Test\Entity1'));
                    $context->getResult()->add(new ApiResource('Test\Entity3'));
                }
            );
        $this->resourcesCache->expects($this->once())
            ->method('getResources')
            ->with($version, $this->identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects($this->once())
            ->method('save')
            ->with($version, $this->identicalTo($requestType), $expectedResources);

        $this->assertEquals(
            $expectedResources,
            $this->loader->getResources($version, $requestType)
        );
    }

    public function testGetResourcesFromCache()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $cachedResources = [
            new ApiResource('Test\Entity1'),
            new ApiResource('Test\Entity3'),
        ];

        $this->processor->expects($this->never())
            ->method('process');
        $this->resourcesCache->expects($this->once())
            ->method('getResources')
            ->with($version, $this->identicalTo($requestType))
            ->willReturn($cachedResources);
        $this->resourcesCache->expects($this->never())
            ->method('save');

        $this->assertEquals(
            $cachedResources,
            $this->loader->getResources($version, $requestType)
        );
    }
}
