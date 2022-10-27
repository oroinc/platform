<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\AnnotationHandler;

use Oro\Bundle\ApiBundle\ApiDoc\AnnotationHandler\RestDocContextProvider;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\DisabledAssociationsConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Options\OptionsContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Symfony\Component\Routing\Route;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RestDocContextProviderTest extends \PHPUnit\Framework\TestCase
{
    private const VERSION = '1.2';

    /** @var RequestType */
    private $requestType;

    /** @var ActionProcessorBagInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $processorBag;

    /** @var RestDocContextProvider */
    private $contextProvider;

    protected function setUp(): void
    {
        $this->requestType = new RequestType([RequestType::REST]);

        $this->processorBag = $this->createMock(ActionProcessorBagInterface::class);

        $docViewDetector = $this->createMock(RestDocViewDetector::class);
        $docViewDetector->expects(self::any())
            ->method('getRequestType')
            ->willReturn($this->requestType);
        $docViewDetector->expects(self::any())
            ->method('getVersion')
            ->willReturn(self::VERSION);

        $this->contextProvider = new RestDocContextProvider(
            $docViewDetector,
            $this->processorBag
        );
    }

    private function getContext(): Context
    {
        return new Context(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );
    }

    private function getSubresourceContext(): SubresourceContext
    {
        return new SubresourceContext(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );
    }

    private function getOptionsContext(): OptionsContext
    {
        return new OptionsContext(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );
    }

    public function testGetContext()
    {
        $action = 'test_action';
        $entityClass = 'Test\Entity';

        $context = $this->getContext();

        $processor = $this->createMock(ActionProcessorInterface::class);
        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with($action)
            ->willReturn($processor);
        $processor->expects(self::once())
            ->method('createContext')
            ->willReturnCallback(function () use ($context, $action) {
                $context->setAction($action);

                return $context;
            });
        $processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (Context $context) use ($entityClass) {
                self::assertEquals($this->requestType, $context->getRequestType());
                self::assertNotSame($this->requestType, $context->getRequestType());
                self::assertEquals(self::VERSION, $context->getVersion());
                self::assertEquals(
                    [new DisabledAssociationsConfigExtra(), new DescriptionsConfigExtra()],
                    $context->getConfigExtras()
                );
                self::assertEquals(ApiActionGroup::INITIALIZE, $context->getLastGroup());
                self::assertTrue($context->isMasterRequest());
                self::assertEquals($entityClass, $context->getClassName());
            });

        self::assertSame(
            $context,
            $this->contextProvider->getContext($action, $entityClass)
        );
    }

    public function testGetContextForSubresource()
    {
        $action = 'test_action';
        $entityClass = 'Test\Entity';
        $associationName = 'association1';

        $context = $this->getSubresourceContext();

        $processor = $this->createMock(ActionProcessorInterface::class);
        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with($action)
            ->willReturn($processor);
        $processor->expects(self::once())
            ->method('createContext')
            ->willReturnCallback(function () use ($context, $action) {
                $context->setAction($action);

                return $context;
            });
        $processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (SubresourceContext $context) use ($entityClass, $associationName, $action) {
                self::assertEquals($this->requestType, $context->getRequestType());
                self::assertNotSame($this->requestType, $context->getRequestType());
                self::assertEquals(self::VERSION, $context->getVersion());
                self::assertEquals(
                    [new DisabledAssociationsConfigExtra(), new DescriptionsConfigExtra()],
                    $context->getConfigExtras()
                );
                self::assertEquals(ApiActionGroup::INITIALIZE, $context->getLastGroup());
                self::assertTrue($context->isMasterRequest());
                self::assertEquals($entityClass, $context->getParentClassName());
                self::assertEquals($associationName, $context->getAssociationName());
                self::assertEquals(
                    [new EntityDefinitionConfigExtra($action)],
                    $context->getParentConfigExtras()
                );
            });

        self::assertSame(
            $context,
            $this->contextProvider->getContext($action, $entityClass, $associationName)
        );
    }

    public function testGetContextForOptions()
    {
        $action = 'test_action';
        $entityClass = 'Test\Entity';
        $route = new Route('/test_route', ['_controller' => 'Test\ApiController::listAction']);

        $context = $this->getOptionsContext();

        $processor = $this->createMock(ActionProcessorInterface::class);
        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with($action)
            ->willReturn($processor);
        $processor->expects(self::once())
            ->method('createContext')
            ->willReturnCallback(function () use ($context, $action) {
                $context->setAction($action);

                return $context;
            });
        $processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (OptionsContext $context) use ($entityClass) {
                self::assertEquals($this->requestType, $context->getRequestType());
                self::assertNotSame($this->requestType, $context->getRequestType());
                self::assertEquals(self::VERSION, $context->getVersion());
                self::assertEquals(
                    [new DisabledAssociationsConfigExtra(), new DescriptionsConfigExtra()],
                    $context->getConfigExtras()
                );
                self::assertEquals(ApiActionGroup::INITIALIZE, $context->getLastGroup());
                self::assertTrue($context->isMasterRequest());
                self::assertEquals($entityClass, $context->getClassName());
                self::assertEquals('list', $context->getActionType());
            });

        self::assertSame(
            $context,
            $this->contextProvider->getContext($action, $entityClass, null, $route)
        );
    }

    public function testGetContextForOptionsForItemWithoutIdController()
    {
        $action = 'test_action';
        $entityClass = 'Test\Entity';
        $route = new Route('/test_route', ['_controller' => 'Test\ApiController::itemWithoutIdAction']);

        $context = $this->getOptionsContext();

        $processor = $this->createMock(ActionProcessorInterface::class);
        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with($action)
            ->willReturn($processor);
        $processor->expects(self::once())
            ->method('createContext')
            ->willReturnCallback(function () use ($context, $action) {
                $context->setAction($action);

                return $context;
            });
        $processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (OptionsContext $context) use ($entityClass) {
                self::assertEquals($this->requestType, $context->getRequestType());
                self::assertNotSame($this->requestType, $context->getRequestType());
                self::assertEquals(self::VERSION, $context->getVersion());
                self::assertEquals(
                    [new DisabledAssociationsConfigExtra(), new DescriptionsConfigExtra()],
                    $context->getConfigExtras()
                );
                self::assertEquals(ApiActionGroup::INITIALIZE, $context->getLastGroup());
                self::assertTrue($context->isMasterRequest());
                self::assertEquals($entityClass, $context->getClassName());
                self::assertEquals('item', $context->getActionType());
            });

        self::assertSame(
            $context,
            $this->contextProvider->getContext($action, $entityClass, null, $route)
        );
    }

    public function testGetContextForOptionsWithActionType()
    {
        $action = 'test_action';
        $entityClass = 'Test\Entity';
        $actionType = 'item';
        $route = new Route(
            '/test_route',
            ['_controller' => 'Test\ApiController::listAction'],
            [],
            ['_action_type' => $actionType]
        );

        $context = $this->getOptionsContext();

        $processor = $this->createMock(ActionProcessorInterface::class);
        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with($action)
            ->willReturn($processor);
        $processor->expects(self::once())
            ->method('createContext')
            ->willReturnCallback(function () use ($context, $action) {
                $context->setAction($action);

                return $context;
            });
        $processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (OptionsContext $context) use ($entityClass, $actionType) {
                self::assertEquals($this->requestType, $context->getRequestType());
                self::assertNotSame($this->requestType, $context->getRequestType());
                self::assertEquals(self::VERSION, $context->getVersion());
                self::assertEquals(
                    [new DisabledAssociationsConfigExtra(), new DescriptionsConfigExtra()],
                    $context->getConfigExtras()
                );
                self::assertEquals(ApiActionGroup::INITIALIZE, $context->getLastGroup());
                self::assertTrue($context->isMasterRequest());
                self::assertEquals($entityClass, $context->getClassName());
                self::assertEquals($actionType, $context->getActionType());
            });

        self::assertSame(
            $context,
            $this->contextProvider->getContext($action, $entityClass, null, $route)
        );
    }

    public function testGetContextForOptionsWithInvalidActionType()
    {
        $action = 'test_action';
        $entityClass = 'Test\Entity';
        $route = new Route(
            '/test_route',
            ['_controller' => 'Test\ApiController::listAction'],
            [],
            ['_action_type' => 'another']
        );

        $context = $this->getOptionsContext();

        $processor = $this->createMock(ActionProcessorInterface::class);
        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->with($action)
            ->willReturn($processor);
        $processor->expects(self::once())
            ->method('createContext')
            ->willReturnCallback(function () use ($context, $action) {
                $context->setAction($action);

                return $context;
            });
        $processor->expects(self::never())
            ->method('process');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'The action type must be one of item, list, subresource, relationship.'
            . ' Given: another. Entity Class: Test\Entity. Action: test_action. Route Path: /test_route.'
            . ' Controller: Test\ApiController::listAction. Use "_action_type" route option to explicitly'
            . ' set the action type.'
        );

        $this->contextProvider->getContext($action, $entityClass, null, $route);
    }

    public function testGetConfig()
    {
        $context = $this->createMock(Context::class);
        $config = new EntityDefinitionConfig();
        $context->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        self::assertSame($config, $this->contextProvider->getConfig($context));
    }

    public function testGetConfigWhenNoConfig()
    {
        $context = $this->createMock(Context::class);
        $context->expects(self::once())
            ->method('getConfig')
            ->willReturn(null);
        $context->expects(self::once())
            ->method('getClassName')
            ->willReturn('Test\Entity');
        $context->expects(self::once())
            ->method('getAction')
            ->willReturn('get_list');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The configuration for "Test\Entity" cannot be loaded. Action: get_list.');

        $this->contextProvider->getConfig($context);
    }

    public function testGetConfigForSubresourceWhenNoConfig()
    {
        $context = $this->createMock(SubresourceContext::class);
        $context->expects(self::once())
            ->method('getConfig')
            ->willReturn(null);
        $context->expects(self::once())
            ->method('getClassName')
            ->willReturn('Test\Entity');
        $context->expects(self::once())
            ->method('getAction')
            ->willReturn('get_list');
        $context->expects(self::once())
            ->method('getAssociationName')
            ->willReturn('association1');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'The configuration for "Test\Entity" cannot be loaded. Action: get_list. Association: association1.'
        );

        $this->contextProvider->getConfig($context);
    }

    public function testGetMetadata()
    {
        $context = $this->createMock(Context::class);
        $metadata = new EntityMetadata('Test\Entity');
        $context->expects(self::once())
            ->method('getMetadata')
            ->willReturn($metadata);

        self::assertSame($metadata, $this->contextProvider->getMetadata($context));
    }

    public function testGetMetadataWhenNoMetadata()
    {
        $context = $this->createMock(Context::class);
        $context->expects(self::once())
            ->method('getMetadata')
            ->willReturn(null);
        $context->expects(self::once())
            ->method('getClassName')
            ->willReturn('Test\Entity');
        $context->expects(self::once())
            ->method('getAction')
            ->willReturn('get_list');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The metadata for "Test\Entity" cannot be loaded. Action: get_list.');

        $this->contextProvider->getMetadata($context);
    }

    public function testGetMetadataForSubresourceWhenNoMetadata()
    {
        $context = $this->createMock(SubresourceContext::class);
        $context->expects(self::once())
            ->method('getMetadata')
            ->willReturn(null);
        $context->expects(self::once())
            ->method('getClassName')
            ->willReturn('Test\Entity');
        $context->expects(self::once())
            ->method('getAction')
            ->willReturn('get_list');
        $context->expects(self::once())
            ->method('getAssociationName')
            ->willReturn('association1');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'The metadata for "Test\Entity" cannot be loaded. Action: get_list. Association: association1.'
        );

        $this->contextProvider->getMetadata($context);
    }
}
