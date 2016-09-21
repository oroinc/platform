<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Processor\CollectSubresources\CollectSubresourcesContext;
use Oro\Bundle\ApiBundle\Provider\SubresourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\RequestType;

class SubresourcesProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $resourcesCache;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $resourcesProvider;

    /** @var SubresourcesProvider */
    protected $subresourcesProvider;

    protected function setUp()
    {
        $this->processor = $this->getMockBuilder('Oro\Bundle\ApiBundle\Processor\CollectSubresourcesProcessor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourcesProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ResourcesProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourcesCache = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ResourcesCache')
            ->disableOriginalConstructor()
            ->getMock();

        $this->subresourcesProvider = new SubresourcesProvider(
            $this->processor,
            $this->resourcesProvider,
            $this->resourcesCache
        );
    }

    public function testGetSubresourcesNoCache()
    {
        $entityClass = 'Test\Entity';
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $resources = [
            new ApiResource('Test\Entity1'),
            new ApiResource('Test\Entity3'),
        ];
        $accessibleResources = ['Test\Entity1'];
        $expectedSubresources = new ApiResourceSubresources($entityClass);
        $expectedSubresources->addSubresource('test');

        $this->processor->expects($this->once())
            ->method('process')
            ->willReturnCallback(
                function (CollectSubresourcesContext $context) use (
                    $version,
                    $requestType,
                    $resources,
                    $accessibleResources
                ) {
                    $this->assertEquals($version, $context->getVersion());
                    $this->assertEquals($requestType, $context->getRequestType());
                    $this->assertEquals(
                        [
                            'Test\Entity1' => new ApiResource('Test\Entity1'),
                            'Test\Entity3' => new ApiResource('Test\Entity3'),
                        ],
                        $context->getResources()
                    );
                    $this->assertEquals($accessibleResources, $context->getAccessibleResources());

                    $subresources1 = new ApiResourceSubresources('Test\Entity');
                    $subresources1->addSubresource('test');
                    $context->getResult()->add($subresources1);
                }
            );
        $this->resourcesProvider->expects($this->once())
            ->method('getResources')
            ->with($version, $this->identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesProvider->expects($this->once())
            ->method('getAccessibleResources')
            ->with($version, $this->identicalTo($requestType))
            ->willReturn($accessibleResources);
        $this->resourcesCache->expects($this->once())
            ->method('getSubresources')
            ->with($entityClass, $version, $this->identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects($this->once())
            ->method('saveSubresources')
            ->with($version, $this->identicalTo($requestType), [$expectedSubresources]);

        $this->assertEquals(
            $expectedSubresources,
            $this->subresourcesProvider->getSubresources($entityClass, $version, $requestType)
        );
    }

    public function testGetSubresourcesForUnknownEntity()
    {
        $entityClass = 'Test\Entity1';
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $resources = [
            new ApiResource('Test\Entity1'),
            new ApiResource('Test\Entity3'),
        ];
        $accessibleResources = ['Test\Entity1'];
        $subresources = new ApiResourceSubresources('Test\Entity2');
        $subresources->addSubresource('test');

        $this->processor->expects($this->once())
            ->method('process')
            ->willReturnCallback(
                function (CollectSubresourcesContext $context) use (
                    $version,
                    $requestType,
                    $resources,
                    $accessibleResources
                ) {
                    $this->assertEquals($version, $context->getVersion());
                    $this->assertEquals($requestType, $context->getRequestType());
                    $this->assertEquals(
                        [
                            'Test\Entity1' => new ApiResource('Test\Entity1'),
                            'Test\Entity3' => new ApiResource('Test\Entity3'),
                        ],
                        $context->getResources()
                    );
                    $this->assertEquals($accessibleResources, $context->getAccessibleResources());

                    $subresources2 = new ApiResourceSubresources('Test\Entity2');
                    $subresources2->addSubresource('test');
                    $context->getResult()->add($subresources2);
                }
            );
        $this->resourcesProvider->expects($this->once())
            ->method('getResources')
            ->with($version, $this->identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesProvider->expects($this->once())
            ->method('getAccessibleResources')
            ->with($version, $this->identicalTo($requestType))
            ->willReturn($accessibleResources);
        $this->resourcesCache->expects($this->once())
            ->method('getSubresources')
            ->with($entityClass, $version, $this->identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects($this->once())
            ->method('saveSubresources')
            ->with($version, $this->identicalTo($requestType), [$subresources]);

        $this->assertNull(
            $this->subresourcesProvider->getSubresources($entityClass, $version, $requestType)
        );
    }

    public function testGetSubresourcesFromCache()
    {
        $entityClass = 'Test\Entity';
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $expectedSubresources = new ApiResourceSubresources($entityClass);
        $expectedSubresources->addSubresource('test');

        $this->processor->expects($this->never())
            ->method('process');
        $this->resourcesProvider->expects($this->never())
            ->method('getResources');
        $this->resourcesProvider->expects($this->never())
            ->method('getAccessibleResources');
        $this->resourcesCache->expects($this->once())
            ->method('getSubresources')
            ->with($entityClass, $version, $this->identicalTo($requestType))
            ->willReturn($expectedSubresources);
        $this->resourcesCache->expects($this->never())
            ->method('saveSubresources');

        $this->assertEquals(
            $expectedSubresources,
            $this->subresourcesProvider->getSubresources($entityClass, $version, $requestType)
        );
    }
}
