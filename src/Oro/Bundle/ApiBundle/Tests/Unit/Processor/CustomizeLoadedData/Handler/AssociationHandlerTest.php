<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeLoadedData\Handler;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\Handler\AssociationHandler;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\ChainProcessor\ParameterBagInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AssociationHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ActionProcessorInterface */
    private $customizationProcessor;

    protected function setUp(): void
    {
        $this->customizationProcessor = $this->createMock(ActionProcessorInterface::class);
    }

    public function handlerCallback(array $data, array $context): array
    {
        $data['callbackKey'] = sprintf('callbackValue for "%s" action', $context['action']);

        return $data;
    }

    public function testWithoutPreviousHandler()
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $rootEntityClass = Entity\Product::class;
        $propertyPath = 'owner';
        $entityClass = Entity\User::class;
        $config = new EntityDefinitionConfig();
        $fieldConfig = $config->addField($propertyPath)->createAndSetTargetEntity();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $handler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $config,
            $configExtras,
            false
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) use (
                    $version,
                    $requestType,
                    $rootEntityClass,
                    $propertyPath,
                    $entityClass,
                    $fieldConfig,
                    $configExtras,
                    $data
                ) {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());
                    self::assertEquals($rootEntityClass, $context->getRootClassName());
                    self::assertEquals($propertyPath, $context->getPropertyPath());
                    self::assertEquals($entityClass, $context->getClassName());
                    self::assertSame($fieldConfig, $context->getConfig());
                    self::assertSame($configExtras, $context->getConfigExtras());
                    self::assertEquals('item', $context->getFirstGroup());
                    self::assertEquals('item', $context->getLastGroup());
                    self::assertEquals($data, $context->getResult());

                    $contextData = $context->getResult();
                    $contextData['anotherKey'] = 'anotherValue';
                    $context->setResult($contextData);
                }
            );

        $handledData = $handler($data, $context);
        self::assertEquals(
            ['key' => 'value', 'anotherKey' => 'anotherValue'],
            $handledData
        );
    }

    public function testWithPreviousHandlerThatIsRedundant()
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $rootEntityClass = Entity\Product::class;
        $propertyPath = 'owner';
        $entityClass = Entity\UserProfile::class;
        $config = new EntityDefinitionConfig();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $previousHandler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            Entity\User::class,
            $config,
            $configExtras,
            false
        );
        $handler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $config,
            $configExtras,
            false,
            $previousHandler
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (CustomizeLoadedDataContext $context) {
                $contextData = $context->getResult();
                $contextData['anotherKey'] = 'anotherValue';
                $context->setResult($contextData);
            });

        $handledData = $handler($data, $context);
        self::assertEquals(
            ['key' => 'value', 'anotherKey' => 'anotherValue'],
            $handledData
        );
    }

    public function testWithSeveralPreviousHandlersAndMiddleLevelPreviousHandlerIsRedundant()
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $rootEntityClass = Entity\Product::class;
        $propertyPath = 'owner';
        $entityClass = Entity\UserProfile::class;
        $config = new EntityDefinitionConfig();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $previousHandler1 = [$this, 'handlerCallback'];
        $previousHandler2 = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            Entity\User::class,
            $config,
            $configExtras,
            false,
            $previousHandler1
        );
        $handler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $config,
            $configExtras,
            false,
            $previousHandler2
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (CustomizeLoadedDataContext $context) {
                $contextData = $context->getResult();
                $contextData['anotherKey'] = 'anotherValue';
                $context->setResult($contextData);
            });

        $handledData = $handler($data, $context);
        self::assertEquals(
            [
                'key'         => 'value',
                'callbackKey' => 'callbackValue for "get" action',
                'anotherKey'  => 'anotherValue'
            ],
            $handledData
        );
    }

    public function testWithPreviousHandlerThatIsNotRedundantDueToVersion()
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $rootEntityClass = Entity\Product::class;
        $propertyPath = 'owner';
        $entityClass = Entity\User::class;
        $config = new EntityDefinitionConfig();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $previousHandler = new AssociationHandler(
            $this->customizationProcessor,
            '1.0',
            $requestType,
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $config,
            $configExtras,
            false
        );
        $handler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $config,
            $configExtras,
            false,
            $previousHandler
        );

        $this->customizationProcessor->expects(self::exactly(2))
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::exactly(2))
            ->method('process')
            ->willReturnCallback(function (CustomizeLoadedDataContext $context) {
                $contextData = $context->getResult();
                if ('1.2' === $context->getVersion()) {
                    $contextData['anotherKey'] = 'anotherValue';
                } elseif ('1.0' === $context->getVersion()) {
                    $contextData['previousKey'] = 'previousValue';
                }
                $context->setResult($contextData);
            });

        $handledData = $handler($data, $context);
        self::assertEquals(
            ['key' => 'value', 'previousKey' => 'previousValue', 'anotherKey' => 'anotherValue'],
            $handledData
        );
    }

    public function testWithPreviousHandlerThatIsNotRedundantDueToRequestType()
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $rootEntityClass = Entity\Product::class;
        $propertyPath = 'owner';
        $entityClass = Entity\User::class;
        $config = new EntityDefinitionConfig();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $previousHandler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            new RequestType(['test1']),
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $config,
            $configExtras,
            false
        );
        $handler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $config,
            $configExtras,
            false,
            $previousHandler
        );

        $this->customizationProcessor->expects(self::exactly(2))
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::exactly(2))
            ->method('process')
            ->willReturnCallback(function (CustomizeLoadedDataContext $context) {
                $contextData = $context->getResult();
                if ('test' === (string)$context->getRequestType()) {
                    $contextData['anotherKey'] = 'anotherValue';
                } elseif ('test1' === (string)$context->getRequestType()) {
                    $contextData['previousKey'] = 'previousValue';
                }
                $context->setResult($contextData);
            });

        $handledData = $handler($data, $context);
        self::assertEquals(
            ['key' => 'value', 'previousKey' => 'previousValue', 'anotherKey' => 'anotherValue'],
            $handledData
        );
    }

    public function testWithPreviousHandlerThatIsNotRedundantDueToRootEntityClass()
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $rootEntityClass = Entity\Product::class;
        $propertyPath = 'owner';
        $entityClass = Entity\User::class;
        $config = new EntityDefinitionConfig();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $previousHandler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            Entity\Account::class,
            $propertyPath,
            $entityClass,
            $config,
            $configExtras,
            false
        );
        $handler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $config,
            $configExtras,
            false,
            $previousHandler
        );

        $this->customizationProcessor->expects(self::exactly(2))
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::exactly(2))
            ->method('process')
            ->willReturnCallback(function (CustomizeLoadedDataContext $context) {
                $contextData = $context->getResult();
                if (Entity\Product::class === $context->getRootClassName()) {
                    $contextData['anotherKey'] = 'anotherValue';
                } elseif (Entity\Account::class === $context->getRootClassName()) {
                    $contextData['previousKey'] = 'previousValue';
                }
                $context->setResult($contextData);
            });

        $handledData = $handler($data, $context);
        self::assertEquals(
            ['key' => 'value', 'previousKey' => 'previousValue', 'anotherKey' => 'anotherValue'],
            $handledData
        );
    }

    public function testWithPreviousHandlerThatIsNotRedundantDueToPropertyPath()
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $rootEntityClass = Entity\Product::class;
        $propertyPath = 'owner';
        $entityClass = Entity\User::class;
        $config = new EntityDefinitionConfig();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $previousHandler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            'organization',
            $entityClass,
            $config,
            $configExtras,
            false
        );
        $handler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $config,
            $configExtras,
            false,
            $previousHandler
        );

        $this->customizationProcessor->expects(self::exactly(2))
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::exactly(2))
            ->method('process')
            ->willReturnCallback(function (CustomizeLoadedDataContext $context) {
                $contextData = $context->getResult();
                if ('owner' === $context->getPropertyPath()) {
                    $contextData['anotherKey'] = 'anotherValue';
                } elseif ('organization' === $context->getPropertyPath()) {
                    $contextData['previousKey'] = 'previousValue';
                }
                $context->setResult($contextData);
            });

        $handledData = $handler($data, $context);
        self::assertEquals(
            ['key' => 'value', 'previousKey' => 'previousValue', 'anotherKey' => 'anotherValue'],
            $handledData
        );
    }

    public function testWithPreviousHandlerThatIsNotRedundantDueToEntityClass()
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $rootEntityClass = Entity\Product::class;
        $propertyPath = 'owner';
        $entityClass = Entity\User::class;
        $config = new EntityDefinitionConfig();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $previousHandler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            Entity\Account::class,
            $config,
            $configExtras,
            false
        );
        $handler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $config,
            $configExtras,
            false,
            $previousHandler
        );

        $this->customizationProcessor->expects(self::exactly(2))
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::exactly(2))
            ->method('process')
            ->willReturnCallback(function (CustomizeLoadedDataContext $context) {
                $contextData = $context->getResult();
                if (Entity\User::class === $context->getClassName()) {
                    $contextData['anotherKey'] = 'anotherValue';
                } elseif (Entity\Account::class === $context->getClassName()) {
                    $contextData['previousKey'] = 'previousValue';
                }
                $context->setResult($contextData);
            });

        $handledData = $handler($data, $context);
        self::assertEquals(
            ['key' => 'value', 'previousKey' => 'previousValue', 'anotherKey' => 'anotherValue'],
            $handledData
        );
    }

    public function testWithPreviousHandlerThatIsNotRedundantDueToHandlerType()
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $rootEntityClass = Entity\Product::class;
        $propertyPath = 'owner';
        $entityClass = Entity\User::class;
        $config = new EntityDefinitionConfig();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $previousHandler = function (array $data) {
            $data['previousKey'] = 'previousValue';

            return $data;
        };
        $handler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $config,
            $configExtras,
            false,
            $previousHandler
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (CustomizeLoadedDataContext $context) {
                $contextData = $context->getResult();
                $contextData['anotherKey'] = 'anotherValue';
                $context->setResult($contextData);
            });

        $handledData = $handler($data, $context);
        self::assertEquals(
            ['key' => 'value', 'previousKey' => 'previousValue', 'anotherKey' => 'anotherValue'],
            $handledData
        );
    }

    public function testConfigForKnownField()
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $rootEntityClass = Entity\Product::class;
        $propertyPath = 'owner';
        $entityClass = Entity\User::class;
        $rootConfig = new EntityDefinitionConfig();
        $config = $rootConfig->addField($propertyPath)->createAndSetTargetEntity();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $handler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $rootConfig,
            $configExtras,
            false
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) use ($rootConfig, $config, $configExtras) {
                    self::assertSame($rootConfig, $context->getRootConfig());
                    self::assertSame($config, $context->getConfig());
                    self::assertSame($configExtras, $context->getConfigExtras());
                }
            );

        $handler($data, $context);
    }

    public function testConfigForUnknownField()
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $rootEntityClass = Entity\Product::class;
        $propertyPath = 'owner';
        $entityClass = Entity\User::class;
        $rootConfig = new EntityDefinitionConfig();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $handler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $rootConfig,
            $configExtras,
            false
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) use ($rootConfig, $configExtras) {
                    self::assertSame($rootConfig, $context->getRootConfig());
                    self::assertNull($context->getConfig());
                    self::assertSame($configExtras, $context->getConfigExtras());
                }
            );

        $handler($data, $context);
    }

    public function testConfigForExcludedField()
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $rootEntityClass = Entity\Product::class;
        $propertyPath = 'owner';
        $entityClass = Entity\User::class;
        $rootConfig = new EntityDefinitionConfig();
        $config = $rootConfig->addField($propertyPath)->createAndSetTargetEntity();
        $rootConfig->getField($propertyPath)->setExcluded();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $handler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $rootConfig,
            $configExtras,
            false
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) use ($rootConfig, $config, $configExtras) {
                    self::assertSame($rootConfig, $context->getRootConfig());
                    self::assertSame($config, $context->getConfig());
                    self::assertSame($configExtras, $context->getConfigExtras());
                }
            );

        $handler($data, $context);
    }

    public function testConfigForNestedAssociationField()
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $rootEntityClass = Entity\Product::class;
        $propertyPath = 'owner.organization';
        $entityClass = Entity\User::class;
        $rootConfig = new EntityDefinitionConfig();
        $associationConfig = $rootConfig->addField('owner')->createAndSetTargetEntity();
        $config = $associationConfig->addField('organization')->createAndSetTargetEntity();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $handler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $rootConfig,
            $configExtras,
            false
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) use ($rootConfig, $config, $configExtras) {
                    self::assertSame($rootConfig, $context->getRootConfig());
                    self::assertSame($config, $context->getConfig());
                    self::assertSame($configExtras, $context->getConfigExtras());
                }
            );

        $handler($data, $context);
    }

    public function testForCollectionHandler()
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $rootEntityClass = Entity\Product::class;
        $propertyPath = 'owner';
        $entityClass = Entity\User::class;
        $config = new EntityDefinitionConfig();
        $fieldConfig = $config->addField($propertyPath)->createAndSetTargetEntity();
        $configExtras = [new EntityDefinitionConfigExtra()];
        $data = ['key' => 'value'];

        $sharedData = $this->createMock(ParameterBagInterface::class);
        $context = ['action' => 'get', 'sharedData' => $sharedData];

        $handler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $config,
            $configExtras,
            true
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) use (
                    $version,
                    $requestType,
                    $rootEntityClass,
                    $propertyPath,
                    $entityClass,
                    $fieldConfig,
                    $configExtras,
                    $data
                ) {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());
                    self::assertEquals($rootEntityClass, $context->getRootClassName());
                    self::assertEquals($propertyPath, $context->getPropertyPath());
                    self::assertEquals($entityClass, $context->getClassName());
                    self::assertSame($fieldConfig, $context->getConfig());
                    self::assertSame($configExtras, $context->getConfigExtras());
                    self::assertEquals('collection', $context->getFirstGroup());
                    self::assertEquals('collection', $context->getLastGroup());
                    self::assertEquals($data, $context->getResult());

                    $contextData = $context->getResult();
                    $contextData['anotherKey'] = 'anotherValue';
                    $context->setResult($contextData);
                }
            );

        $handledData = $handler($data, $context);
        self::assertEquals(
            ['key' => 'value', 'anotherKey' => 'anotherValue'],
            $handledData
        );
    }
}
