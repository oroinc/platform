<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\GetProcessor;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Provider\ResourcesWithoutIdentifierLoader;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ProcessorBagInterface;

class ResourcesWithoutIdentifierLoaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ActionProcessorBagInterface */
    private $processorBag;

    /** @var ResourcesWithoutIdentifierLoader */
    private $loader;

    protected function setUp()
    {
        $this->processorBag = $this->createMock(ActionProcessorBagInterface::class);

        $this->loader = new ResourcesWithoutIdentifierLoader($this->processorBag);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|GetProcessor
     */
    private function getProcessorMock()
    {
        return $this->getMockBuilder(GetProcessor::class)
            ->setConstructorArgs([
                $this->createMock(ProcessorBagInterface::class),
                ApiActions::GET,
                $this->createMock(ConfigProvider::class),
                $this->createMock(MetadataProvider::class)
            ])
            ->getMock();
    }

    public function testLoadForEntityWithoutIdentifierFields()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);
        $resources = [new ApiResource('Test\Entity1')];

        $processor = $this->getProcessorMock();
        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with(ApiActions::GET)
            ->willReturn($processor);
        $processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (GetContext $context) use ($version, $requestType) {
                self::assertEquals($version, $context->getVersion());
                self::assertEquals($requestType, $context->getRequestType());
                self::assertEquals('Test\Entity1', $context->getClassName());
                self::assertEquals(
                    [new EntityDefinitionConfigExtra(ApiActions::GET), new FilterIdentifierFieldsConfigExtra()],
                    $context->getConfigExtras()
                );
                self::assertEquals('initialize', $context->getLastGroup());

                $config = new EntityDefinitionConfig();
                $context->setConfig($config);
            });

        self::assertEquals(
            ['Test\Entity1'],
            $this->loader->load($version, $requestType, $resources)
        );
    }

    public function testLoadForEntityWithIdentifierFields()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);
        $resources = [new ApiResource('Test\Entity1')];

        $processor = $this->getProcessorMock();
        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with(ApiActions::GET)
            ->willReturn($processor);
        $processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (GetContext $context) {
                $config = new EntityDefinitionConfig();
                $config->setIdentifierFieldNames(['id']);
                $context->setConfig($config);
            });

        self::assertEquals(
            [],
            $this->loader->load($version, $requestType, $resources)
        );
    }
}
