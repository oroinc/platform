<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Processor\CollectPublicResources\CollectPublicResourcesContext;
use Oro\Bundle\ApiBundle\Provider\PublicResourcesLoader;
use Oro\Bundle\ApiBundle\Request\PublicResource;
use Oro\Bundle\ApiBundle\Request\RequestType;

class PublicResourcesLoaderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $processor;

    /** @var PublicResourcesLoader */
    protected $loader;

    protected function setUp()
    {
        $this->processor = $this->getMockBuilder('Oro\Bundle\ApiBundle\Processor\CollectPublicResourcesProcessor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->loader = new PublicResourcesLoader($this->processor);
    }

    public function testGetResources()
    {
        $version     = '1.2.3';
        $requestType = [RequestType::REST, RequestType::JSON_API];

        $this->processor->expects($this->once())
            ->method('process')
            ->willReturnCallback(
                function (CollectPublicResourcesContext $context) use ($version, $requestType) {
                    $this->assertEquals($version, $context->getVersion());
                    $this->assertEquals($requestType, $context->getRequestType());

                    $context->getResult()->add(new PublicResource('Test\Entity1'));
                    $context->getResult()->set(2, new PublicResource('Test\Entity3'));
                }
            );

        $this->assertEquals(
            [
                new PublicResource('Test\Entity1'),
                new PublicResource('Test\Entity3'),
            ],
            $this->loader->getResources($version, $requestType)
        );
    }
}
