<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeLoadedData\Handler;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\Handler\AssociationHandler;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

class AssociationHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ActionProcessorInterface */
    private $customizationProcessor;

    protected function setUp()
    {
        $this->customizationProcessor = $this->createMock(ActionProcessorInterface::class);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function handlerCallback(array $data)
    {
        $data['callbackKey'] = 'callbackValue';

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
        $data = ['key' => 'value'];

        $handler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $config,
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
                    $data
                ) {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());
                    self::assertEquals($rootEntityClass, $context->getRootClassName());
                    self::assertEquals($propertyPath, $context->getPropertyPath());
                    self::assertEquals($entityClass, $context->getClassName());
                    self::assertSame($fieldConfig, $context->getConfig());
                    self::assertEquals('item', $context->getFirstGroup());
                    self::assertEquals('item', $context->getLastGroup());
                    self::assertEquals($data, $context->getResult());

                    $contextData = $context->getResult();
                    $contextData['anotherKey'] = 'anotherValue';
                    $context->setResult($contextData);
                }
            );

        $handledData = \call_user_func($handler, $data);
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
        $data = ['key' => 'value'];

        $previousHandler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            Entity\User::class,
            $config,
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
            false,
            $previousHandler
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) {
                    $contextData = $context->getResult();
                    $contextData['anotherKey'] = 'anotherValue';
                    $context->setResult($contextData);
                }
            );

        $handledData = \call_user_func($handler, $data);
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
        $data = ['key' => 'value'];

        $previousHandler1 = [$this, 'handlerCallback'];
        $previousHandler2 = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            Entity\User::class,
            $config,
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
            false,
            $previousHandler2
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) {
                    $contextData = $context->getResult();
                    $contextData['anotherKey'] = 'anotherValue';
                    $context->setResult($contextData);
                }
            );

        $handledData = \call_user_func($handler, $data);
        self::assertEquals(
            ['key' => 'value', 'callbackKey' => 'callbackValue', 'anotherKey' => 'anotherValue'],
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
        $data = ['key' => 'value'];

        $previousHandler = new AssociationHandler(
            $this->customizationProcessor,
            '1.0',
            $requestType,
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $config,
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
            false,
            $previousHandler
        );

        $this->customizationProcessor->expects(self::exactly(2))
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::exactly(2))
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) {
                    $contextData = $context->getResult();
                    if ('1.2' === $context->getVersion()) {
                        $contextData['anotherKey'] = 'anotherValue';
                    } elseif ('1.0' === $context->getVersion()) {
                        $contextData['previousKey'] = 'previousValue';
                    }
                    $context->setResult($contextData);
                }
            );

        $handledData = \call_user_func($handler, $data);
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
        $data = ['key' => 'value'];

        $previousHandler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            new RequestType(['test1']),
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $config,
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
            false,
            $previousHandler
        );

        $this->customizationProcessor->expects(self::exactly(2))
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::exactly(2))
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) {
                    $contextData = $context->getResult();
                    if ('test' === (string)$context->getRequestType()) {
                        $contextData['anotherKey'] = 'anotherValue';
                    } elseif ('test1' === (string)$context->getRequestType()) {
                        $contextData['previousKey'] = 'previousValue';
                    }
                    $context->setResult($contextData);
                }
            );

        $handledData = \call_user_func($handler, $data);
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
        $data = ['key' => 'value'];

        $previousHandler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            Entity\Account::class,
            $propertyPath,
            $entityClass,
            $config,
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
            false,
            $previousHandler
        );

        $this->customizationProcessor->expects(self::exactly(2))
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::exactly(2))
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) {
                    $contextData = $context->getResult();
                    if (Entity\Product::class === $context->getRootClassName()) {
                        $contextData['anotherKey'] = 'anotherValue';
                    } elseif (Entity\Account::class === $context->getRootClassName()) {
                        $contextData['previousKey'] = 'previousValue';
                    }
                    $context->setResult($contextData);
                }
            );

        $handledData = \call_user_func($handler, $data);
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
        $data = ['key' => 'value'];

        $previousHandler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            'organization',
            $entityClass,
            $config,
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
            false,
            $previousHandler
        );

        $this->customizationProcessor->expects(self::exactly(2))
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::exactly(2))
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) {
                    $contextData = $context->getResult();
                    if ('owner' === $context->getPropertyPath()) {
                        $contextData['anotherKey'] = 'anotherValue';
                    } elseif ('organization' === $context->getPropertyPath()) {
                        $contextData['previousKey'] = 'previousValue';
                    }
                    $context->setResult($contextData);
                }
            );

        $handledData = \call_user_func($handler, $data);
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
        $data = ['key' => 'value'];

        $previousHandler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            Entity\Account::class,
            $config,
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
            false,
            $previousHandler
        );

        $this->customizationProcessor->expects(self::exactly(2))
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::exactly(2))
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) {
                    $contextData = $context->getResult();
                    if (Entity\User::class === $context->getClassName()) {
                        $contextData['anotherKey'] = 'anotherValue';
                    } elseif (Entity\Account::class === $context->getClassName()) {
                        $contextData['previousKey'] = 'previousValue';
                    }
                    $context->setResult($contextData);
                }
            );

        $handledData = \call_user_func($handler, $data);
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
        $data = ['key' => 'value'];

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
            false,
            $previousHandler
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) {
                    $contextData = $context->getResult();
                    $contextData['anotherKey'] = 'anotherValue';
                    $context->setResult($contextData);
                }
            );

        $handledData = \call_user_func($handler, $data);
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
        $data = ['key' => 'value'];

        $handler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $rootConfig,
            false
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) use ($rootConfig, $config) {
                    self::assertSame($rootConfig, $context->getRootConfig());
                    self::assertSame($config, $context->getConfig());
                }
            );

        \call_user_func($handler, $data);
    }

    public function testConfigForUnknownField()
    {
        $version = '1.2';
        $requestType = new RequestType(['test']);
        $rootEntityClass = Entity\Product::class;
        $propertyPath = 'owner';
        $entityClass = Entity\User::class;
        $rootConfig = new EntityDefinitionConfig();
        $data = ['key' => 'value'];

        $handler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $rootConfig,
            false
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) use ($rootConfig) {
                    self::assertSame($rootConfig, $context->getRootConfig());
                    self::assertNull($context->getConfig());
                }
            );

        \call_user_func($handler, $data);
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
        $data = ['key' => 'value'];

        $handler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $rootConfig,
            false
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) use ($rootConfig, $config) {
                    self::assertSame($rootConfig, $context->getRootConfig());
                    self::assertSame($config, $context->getConfig());
                }
            );

        \call_user_func($handler, $data);
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
        $data = ['key' => 'value'];

        $handler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $rootConfig,
            false
        );

        $this->customizationProcessor->expects(self::once())
            ->method('createContext')
            ->willReturn(new CustomizeLoadedDataContext());
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) use ($rootConfig, $config) {
                    self::assertSame($rootConfig, $context->getRootConfig());
                    self::assertSame($config, $context->getConfig());
                }
            );

        \call_user_func($handler, $data);
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
        $data = ['key' => 'value'];

        $handler = new AssociationHandler(
            $this->customizationProcessor,
            $version,
            $requestType,
            $rootEntityClass,
            $propertyPath,
            $entityClass,
            $config,
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
                    $data
                ) {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());
                    self::assertEquals($rootEntityClass, $context->getRootClassName());
                    self::assertEquals($propertyPath, $context->getPropertyPath());
                    self::assertEquals($entityClass, $context->getClassName());
                    self::assertSame($fieldConfig, $context->getConfig());
                    self::assertEquals('collection', $context->getFirstGroup());
                    self::assertEquals('collection', $context->getLastGroup());
                    self::assertEquals($data, $context->getResult());

                    $contextData = $context->getResult();
                    $contextData['anotherKey'] = 'anotherValue';
                    $context->setResult($contextData);
                }
            );

        $handledData = \call_user_func($handler, $data);
        self::assertEquals(
            ['key' => 'value', 'anotherKey' => 'anotherValue'],
            $handledData
        );
    }
}
