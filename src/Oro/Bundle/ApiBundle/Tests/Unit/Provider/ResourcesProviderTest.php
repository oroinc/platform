<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;

class ResourcesProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $resourcesCache;

    /** @var ResourcesProvider */
    protected $resourcesProvider;

    protected function setUp()
    {
        $this->processor = $this->getMockBuilder('Oro\Bundle\ApiBundle\Processor\CollectResourcesProcessor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourcesCache = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ResourcesCache')
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourcesProvider = new ResourcesProvider($this->processor, $this->resourcesCache);
    }

    public function testGetResourcesNoCache()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $expectedResources = [
            new ApiResource('Test\Entity1'),
            new ApiResource('Test\Entity3'),
        ];
        $expectedAccessibleResources = ['Test\Entity3'];

        $this->processor->expects($this->once())
            ->method('process')
            ->willReturnCallback(
                function (CollectResourcesContext $context) use ($version, $requestType) {
                    $this->assertEquals($version, $context->getVersion());
                    $this->assertEquals($requestType, $context->getRequestType());

                    $context->getResult()->add(new ApiResource('Test\Entity1'));
                    $context->getResult()->add(new ApiResource('Test\Entity3'));

                    $context->setAccessibleResources(['Test\Entity3']);
                }
            );
        $this->resourcesCache->expects($this->once())
            ->method('getResources')
            ->with($version, $this->identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects($this->once())
            ->method('saveResources')
            ->with($version, $this->identicalTo($requestType), $expectedResources, $expectedAccessibleResources);

        $this->assertEquals(
            $expectedResources,
            $this->resourcesProvider->getResources($version, $requestType)
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
            ->method('saveResources');

        $this->assertEquals(
            $cachedResources,
            $this->resourcesProvider->getResources($version, $requestType)
        );
    }

    public function testGetAccessibleResourcesWhenCacheExists()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $cachedData = [
            'Test\Entity1' => false,
            'Test\Entity3' => true
        ];

        $this->processor->expects($this->never())
            ->method('process');
        $this->resourcesCache->expects($this->once())
            ->method('getAccessibleResources')
            ->with($version, $this->identicalTo($requestType))
            ->willReturn($cachedData);
        $this->resourcesCache->expects($this->never())
            ->method('saveResources');

        $this->assertEquals(
            ['Test\Entity3'],
            $this->resourcesProvider->getAccessibleResources($version, $requestType)
        );
    }

    public function testGetAccessibleResourcesWhenCacheDoesNotExist()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $cachedData = [
            'Test\Entity1' => false,
            'Test\Entity3' => true
        ];
        $expectedResources = [
            new ApiResource('Test\Entity1'),
            new ApiResource('Test\Entity3'),
        ];
        $expectedAccessibleResources = ['Test\Entity3'];

        $this->processor->expects($this->once())
            ->method('process')
            ->willReturnCallback(
                function (CollectResourcesContext $context) use ($version, $requestType) {
                    $this->assertEquals($version, $context->getVersion());
                    $this->assertEquals($requestType, $context->getRequestType());

                    $context->getResult()->add(new ApiResource('Test\Entity1'));
                    $context->getResult()->add(new ApiResource('Test\Entity3'));

                    $context->setAccessibleResources(['Test\Entity3']);
                }
            );
        $this->resourcesCache->expects($this->at(0))
            ->method('getAccessibleResources')
            ->with($version, $this->identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects($this->at(1))
            ->method('getResources')
            ->with($version, $this->identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects($this->at(2))
            ->method('saveResources')
            ->with($version, $this->identicalTo($requestType), $expectedResources, $expectedAccessibleResources);
        $this->resourcesCache->expects($this->at(3))
            ->method('getAccessibleResources')
            ->with($version, $this->identicalTo($requestType))
            ->willReturn($cachedData);

        $this->assertEquals(
            ['Test\Entity3'],
            $this->resourcesProvider->getAccessibleResources($version, $requestType)
        );
    }

    public function testIsResourceAccessibleWhenCacheExists()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $cachedData = [
            'Test\Entity1' => false,
            'Test\Entity3' => true
        ];

        $this->processor->expects($this->never())
            ->method('process');
        $this->resourcesCache->expects($this->once())
            ->method('getAccessibleResources')
            ->with($version, $this->identicalTo($requestType))
            ->willReturn($cachedData);
        $this->resourcesCache->expects($this->never())
            ->method('saveResources');

        $this->assertFalse(
            $this->resourcesProvider->isResourceAccessible('Test\Entity1', $version, $requestType)
        );
        $this->assertFalse(
            $this->resourcesProvider->isResourceAccessible('Test\Entity2', $version, $requestType)
        );
        $this->assertTrue(
            $this->resourcesProvider->isResourceAccessible('Test\Entity3', $version, $requestType)
        );
    }

    public function testIsResourceAccessibleWhenCacheDoesNotExist()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $cachedData = [
            'Test\Entity1' => false,
            'Test\Entity3' => true
        ];
        $expectedResources = [
            new ApiResource('Test\Entity1'),
            new ApiResource('Test\Entity3'),
        ];
        $expectedAccessibleResources = ['Test\Entity3'];

        $this->processor->expects($this->once())
            ->method('process')
            ->willReturnCallback(
                function (CollectResourcesContext $context) use ($version, $requestType) {
                    $this->assertEquals($version, $context->getVersion());
                    $this->assertEquals($requestType, $context->getRequestType());

                    $context->getResult()->add(new ApiResource('Test\Entity1'));
                    $context->getResult()->add(new ApiResource('Test\Entity3'));

                    $context->setAccessibleResources(['Test\Entity3']);
                }
            );
        $this->resourcesCache->expects($this->at(0))
            ->method('getAccessibleResources')
            ->with($version, $this->identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects($this->at(1))
            ->method('getResources')
            ->with($version, $this->identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects($this->at(2))
            ->method('saveResources')
            ->with($version, $this->identicalTo($requestType), $expectedResources, $expectedAccessibleResources);
        $this->resourcesCache->expects($this->at(3))
            ->method('getAccessibleResources')
            ->with($version, $this->identicalTo($requestType))
            ->willReturn($cachedData);

        $this->assertFalse(
            $this->resourcesProvider->isResourceAccessible('Test\Entity1', $version, $requestType)
        );
        $this->assertFalse(
            $this->resourcesProvider->isResourceAccessible('Test\Entity2', $version, $requestType)
        );
        $this->assertTrue(
            $this->resourcesProvider->isResourceAccessible('Test\Entity3', $version, $requestType)
        );
    }

    public function testIsResourceKnownWhenCacheExists()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $cachedData = [
            'Test\Entity1' => false,
            'Test\Entity3' => true
        ];

        $this->processor->expects($this->never())
            ->method('process');
        $this->resourcesCache->expects($this->once())
            ->method('getAccessibleResources')
            ->with($version, $this->identicalTo($requestType))
            ->willReturn($cachedData);
        $this->resourcesCache->expects($this->never())
            ->method('saveResources');

        $this->assertTrue(
            $this->resourcesProvider->isResourceKnown('Test\Entity1', $version, $requestType)
        );
        $this->assertFalse(
            $this->resourcesProvider->isResourceKnown('Test\Entity2', $version, $requestType)
        );
        $this->assertTrue(
            $this->resourcesProvider->isResourceKnown('Test\Entity3', $version, $requestType)
        );
    }

    public function testIsResourceKnownWhenCacheDoesNotExist()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $cachedData = [
            'Test\Entity1' => false,
            'Test\Entity3' => true
        ];
        $expectedResources = [
            new ApiResource('Test\Entity1'),
            new ApiResource('Test\Entity3'),
        ];
        $expectedAccessibleResources = ['Test\Entity3'];

        $this->processor->expects($this->once())
            ->method('process')
            ->willReturnCallback(
                function (CollectResourcesContext $context) use ($version, $requestType) {
                    $this->assertEquals($version, $context->getVersion());
                    $this->assertEquals($requestType, $context->getRequestType());

                    $context->getResult()->add(new ApiResource('Test\Entity1'));
                    $context->getResult()->add(new ApiResource('Test\Entity3'));

                    $context->setAccessibleResources(['Test\Entity3']);
                }
            );
        $this->resourcesCache->expects($this->at(0))
            ->method('getAccessibleResources')
            ->with($version, $this->identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects($this->at(1))
            ->method('getResources')
            ->with($version, $this->identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects($this->at(2))
            ->method('saveResources')
            ->with($version, $this->identicalTo($requestType), $expectedResources, $expectedAccessibleResources);
        $this->resourcesCache->expects($this->at(3))
            ->method('getAccessibleResources')
            ->with($version, $this->identicalTo($requestType))
            ->willReturn($cachedData);

        $this->assertTrue(
            $this->resourcesProvider->isResourceKnown('Test\Entity1', $version, $requestType)
        );
        $this->assertFalse(
            $this->resourcesProvider->isResourceKnown('Test\Entity2', $version, $requestType)
        );
        $this->assertTrue(
            $this->resourcesProvider->isResourceKnown('Test\Entity3', $version, $requestType)
        );
    }
}
