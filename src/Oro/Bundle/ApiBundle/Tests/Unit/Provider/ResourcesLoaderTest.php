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

    /** @var ResourcesLoader */
    protected $loader;

    protected function setUp()
    {
        $this->processor = $this->getMockBuilder('Oro\Bundle\ApiBundle\Processor\CollectResourcesProcessor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->loader = new ResourcesLoader($this->processor);
    }

    public function testGetResources()
    {
        $version     = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $this->processor->expects($this->once())
            ->method('process')
            ->willReturnCallback(
                function (CollectResourcesContext $context) use ($version, $requestType) {
                    $this->assertEquals($version, $context->getVersion());
                    $this->assertEquals($requestType, $context->getRequestType());

                    $context->getResult()->add(new ApiResource('Test\Entity1'));
                    $context->getResult()->set(2, new ApiResource('Test\Entity3'));
                }
            );

        $this->assertEquals(
            [
                new ApiResource('Test\Entity1'),
                new ApiResource('Test\Entity3'),
            ],
            $this->loader->getResources($version, $requestType)
        );
    }
}
