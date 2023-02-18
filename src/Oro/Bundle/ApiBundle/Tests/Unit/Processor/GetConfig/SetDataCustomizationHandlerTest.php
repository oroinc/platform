<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\Handler\AssociationHandler;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\Handler\EntityHandler;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedDataProcessor;
use Oro\Bundle\ApiBundle\Processor\GetConfig\SetDataCustomizationHandler;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Component\ChainProcessor\ParameterBagInterface;
use Oro\Component\ChainProcessor\ProcessorBagInterface;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;

class SetDataCustomizationHandlerTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|CustomizeLoadedDataProcessor */
    private $customizationProcessor;

    /** @var SetDataCustomizationHandler */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customizationProcessor = $this->getMockBuilder(CustomizeLoadedDataProcessor::class)
            ->onlyMethods(['process'])
            ->setConstructorArgs([$this->createMock(ProcessorBagInterface::class), 'customize_loaded_data'])
            ->getMock();

        $this->processor = new SetDataCustomizationHandler($this->customizationProcessor);
    }

    public function testProcessForEmptyConfig()
    {
        $config = [];

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig([], $configObject);
    }

    public function testProcessForNotCompletedConfig()
    {
        $config = [
            'fields' => [
                'field1' => null
            ]
        ];

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'field1' => null
                ]
            ],
            $configObject
        );
    }

    public function testProcessForEntityWithoutAssociations()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'field21' => null,
                        'field22' => [
                            'exclusion_policy' => 'all',
                            'fields'           => [
                                'field221' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        self::assertInstanceOf(
            EntityHandler::class,
            $configObject->getPostSerializeHandler()
        );
        self::assertNull(
            $configObject
                ->getField('field1')
                ->getTargetEntity()
        );
        self::assertNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getPostSerializeHandler()
        );

        self::assertInstanceOf(
            EntityHandler::class,
            $configObject->getPostSerializeCollectionHandler()
        );

        [$assert, $expect] = $this->getRootHandlerAssertion($configObject);
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback($expect);
        $assert();
    }

    public function testProcessForGetListTargetAction()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null
            ]
        ];

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setTargetAction(ApiAction::GET_LIST);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        self::assertInstanceOf(
            EntityHandler::class,
            $configObject->getPostSerializeHandler()
        );
        self::assertNull(
            $configObject
                ->getField('field1')
                ->getTargetEntity()
        );

        self::assertInstanceOf(
            EntityHandler::class,
            $configObject->getPostSerializeCollectionHandler()
        );

        [$rootAssert, $rootExpect] = $this->getRootHandlerAssertion($configObject);
        [$rootCollectionAssert, $rootCollectionExpect] = $this->getRootHandlerAssertion($configObject, 'collection');
        $this->customizationProcessor->expects(self::exactly(2))
            ->method('process')
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback($rootExpect),
                new ReturnCallback($rootCollectionExpect)
            );
        foreach ([$rootAssert, $rootCollectionAssert] as $assert) {
            $assert();
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForEntityWithAssociations()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'exclusion_policy' => 'all',
                    'target_class'     => 'Test\Field2Target',
                    'fields'           => [
                        'field21' => null,
                        'field22' => [
                            'exclusion_policy' => 'all',
                            'target_class'     => 'Test\Field22Target',
                            'fields'           => [
                                'field221' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        self::assertInstanceOf(
            EntityHandler::class,
            $configObject->getPostSerializeHandler()
        );
        self::assertNull(
            $configObject
                ->getField('field1')
                ->getTargetEntity()
        );
        self::assertInstanceOf(
            AssociationHandler::class,
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getPostSerializeHandler()
        );
        self::assertInstanceOf(
            AssociationHandler::class,
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getPostSerializeCollectionHandler()
        );
        self::assertNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field21')
                ->getTargetEntity()
        );
        self::assertInstanceOf(
            AssociationHandler::class,
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field22')
                ->getTargetEntity()
                ->getPostSerializeHandler()
        );
        self::assertInstanceOf(
            AssociationHandler::class,
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field22')
                ->getTargetEntity()
                ->getPostSerializeCollectionHandler()
        );
        self::assertNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field22')
                ->getTargetEntity()
                ->getField('field221')
                ->getTargetEntity()
        );

        [$rootAssert, $rootExpect] = $this->getRootHandlerAssertion($configObject);
        [$field2Assert, $field2Expect] = $this->getChildHandlerAssertion(
            $configObject,
            $configObject->getField('field2')->getTargetEntity(),
            'Test\Field2Target',
            'field2'
        );
        [$field22Assert, $field22Expect] = $this->getChildHandlerAssertion(
            $configObject,
            $configObject->getField('field2')->getTargetEntity()->getField('field22')->getTargetEntity(),
            'Test\Field22Target',
            'field2.field22'
        );
        $this->customizationProcessor->expects(self::exactly(3))
            ->method('process')
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback($rootExpect),
                new ReturnCallback($field2Expect),
                new ReturnCallback($field22Expect)
            );
        foreach ([$rootAssert, $field2Assert, $field22Assert] as $assert) {
            $assert();
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForEntityWithCollectionValuedAssociations()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'exclusion_policy' => 'all',
                    'target_class'     => 'Test\Field2Target',
                    'target_type'      => 'to-many',
                    'fields'           => [
                        'field21' => null,
                        'field22' => [
                            'exclusion_policy' => 'all',
                            'target_class'     => 'Test\Field22Target',
                            'target_type'      => 'to-many',
                            'fields'           => [
                                'field221' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        self::assertInstanceOf(
            EntityHandler::class,
            $configObject->getPostSerializeHandler()
        );
        self::assertNull(
            $configObject
                ->getField('field1')
                ->getTargetEntity()
        );
        self::assertInstanceOf(
            AssociationHandler::class,
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getPostSerializeHandler()
        );
        self::assertInstanceOf(
            AssociationHandler::class,
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getPostSerializeCollectionHandler()
        );
        self::assertNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field21')
                ->getTargetEntity()
        );
        self::assertInstanceOf(
            AssociationHandler::class,
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field22')
                ->getTargetEntity()
                ->getPostSerializeHandler()
        );
        self::assertInstanceOf(
            AssociationHandler::class,
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field22')
                ->getTargetEntity()
                ->getPostSerializeCollectionHandler()
        );
        self::assertNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field22')
                ->getTargetEntity()
                ->getField('field221')
                ->getTargetEntity()
        );

        [$rootAssert, $rootExpect] = $this->getRootHandlerAssertion($configObject);
        [$field2Assert, $field2Expect] = $this->getChildHandlerAssertion(
            $configObject,
            $configObject->getField('field2')->getTargetEntity(),
            'Test\Field2Target',
            'field2'
        );
        [$field2CollectionAssert, $field2CollectionExpect] = $this->getChildHandlerAssertion(
            $configObject,
            $configObject->getField('field2')->getTargetEntity(),
            'Test\Field2Target',
            'field2',
            'collection'
        );
        [$field22Assert, $field22Expect] = $this->getChildHandlerAssertion(
            $configObject,
            $configObject->getField('field2')->getTargetEntity()->getField('field22')->getTargetEntity(),
            'Test\Field22Target',
            'field2.field22'
        );
        [$field22CollectionAssert, $field22CollectionExpect] = $this->getChildHandlerAssertion(
            $configObject,
            $configObject->getField('field2')->getTargetEntity()->getField('field22')->getTargetEntity(),
            'Test\Field22Target',
            'field2.field22',
            'collection'
        );
        $this->customizationProcessor->expects(self::exactly(5))
            ->method('process')
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback($rootExpect),
                new ReturnCallback($field2Expect),
                new ReturnCallback($field2CollectionExpect),
                new ReturnCallback($field22Expect),
                new ReturnCallback($field22CollectionExpect)
            );
        $asserts = [$rootAssert, $field2Assert, $field2CollectionAssert, $field22Assert, $field22CollectionAssert];
        foreach ($asserts as $assert) {
            $assert();
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForEntityWithRenamedAssociations()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'exclusion_policy' => 'all',
                    'target_class'     => 'Test\Field2Target',
                    'property_path'    => 'realField2',
                    'fields'           => [
                        'field21' => null,
                        'field22' => [
                            'exclusion_policy' => 'all',
                            'target_class'     => 'Test\Field22Target',
                            'property_path'    => 'realField22',
                            'fields'           => [
                                'field221' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        self::assertInstanceOf(
            EntityHandler::class,
            $configObject->getPostSerializeHandler()
        );
        self::assertNull(
            $configObject
                ->getField('field1')
                ->getTargetEntity()
        );
        self::assertInstanceOf(
            AssociationHandler::class,
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getPostSerializeHandler()
        );
        self::assertNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field21')
                ->getTargetEntity()
        );
        self::assertInstanceOf(
            AssociationHandler::class,
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field22')
                ->getTargetEntity()
                ->getPostSerializeHandler()
        );
        self::assertNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field22')
                ->getTargetEntity()
                ->getField('field221')
                ->getTargetEntity()
        );

        [$rootAssert, $rootExpect] = $this->getRootHandlerAssertion($configObject);
        [$field2Assert, $field2Expect] = $this->getChildHandlerAssertion(
            $configObject,
            $configObject->getField('field2')->getTargetEntity(),
            'Test\Field2Target',
            'field2'
        );
        [$field22Assert, $field22Expect] = $this->getChildHandlerAssertion(
            $configObject,
            $configObject->getField('field2')->getTargetEntity()->getField('field22')->getTargetEntity(),
            'Test\Field22Target',
            'field2.field22'
        );
        $this->customizationProcessor->expects(self::exactly(3))
            ->method('process')
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback($rootExpect),
                new ReturnCallback($field2Expect),
                new ReturnCallback($field22Expect)
            );
        foreach ([$rootAssert, $field2Assert, $field22Assert] as $assert) {
            $assert();
        }
    }

    public function testProcessForEntityWithAssociationAsField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'exclusion_policy' => 'all',
                    'data_type'        => 'object',
                    'target_class'     => 'Test\Field2Target',
                    'fields'           => [
                        'field21' => null,
                        'field22' => [
                            'exclusion_policy' => 'all',
                            'target_class'     => 'Test\Field22Target',
                            'fields'           => [
                                'field221' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        self::assertInstanceOf(
            EntityHandler::class,
            $configObject->getPostSerializeHandler()
        );
        self::assertNull(
            $configObject
                ->getField('field1')
                ->getTargetEntity()
        );
        self::assertNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getPostSerializeHandler()
        );
        self::assertNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field21')
                ->getTargetEntity()
        );
        self::assertNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field22')
                ->getTargetEntity()
                ->getPostSerializeHandler()
        );
        self::assertNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field22')
                ->getTargetEntity()
                ->getField('field221')
                ->getTargetEntity()
        );

        [$assert, $expect] = $this->getRootHandlerAssertion($configObject);
        $this->customizationProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback($expect);
        $assert();
    }

    /**
     * @return callable[] [assertion, expectation]
     */
    private function getRootHandlerAssertion(EntityDefinitionConfig $configObject, string $handlerType = ''): array
    {
        $sourceDataItem = ['source data'];
        $processedDataItem = ['processed data'];
        $expectation = function (CustomizeLoadedDataContext $context) use (
            $sourceDataItem,
            $processedDataItem,
            $configObject
        ) {
            self::assertEquals($this->context->getVersion(), $context->getVersion());
            self::assertEquals($this->context->getRequestType(), $context->getRequestType());
            self::assertEquals($this->context->getClassName(), $context->getClassName());
            self::assertSame($configObject, $context->getConfig());
            self::assertEquals($sourceDataItem, $context->getResult());

            $context->setResult($processedDataItem);
        };

        $assertion = function () use ($configObject, $processedDataItem, $sourceDataItem, $handlerType) {
            $getter = 'getPostSerialize' . ucfirst($handlerType) . 'Handler';
            $rootHandler = $configObject->{$getter}();
            self::assertEquals(
                $processedDataItem,
                $rootHandler($sourceDataItem, ['sharedData' => $this->createMock(ParameterBagInterface::class)])
            );
        };

        return [$assertion, $expectation];
    }

    /**
     * @return callable[] [assertion, expectation]
     */
    private function getChildHandlerAssertion(
        EntityDefinitionConfig $configObject,
        EntityDefinitionConfig $childConfigObject,
        string $childEntityClass,
        string $fieldPath,
        string $handlerType = ''
    ): array {
        $sourceDataItem = ['source data'];
        $processedDataItem = ['processed data'];
        $expectation = function (CustomizeLoadedDataContext $context) use (
            $sourceDataItem,
            $processedDataItem,
            $childEntityClass,
            $fieldPath,
            $configObject,
            $childConfigObject
        ) {
            self::assertEquals($this->context->getVersion(), $context->getVersion());
            self::assertEquals($this->context->getRequestType(), $context->getRequestType());
            self::assertEquals($this->context->getClassName(), $context->getRootClassName());
            self::assertEquals($childEntityClass, $context->getClassName());
            self::assertEquals($fieldPath, $context->getPropertyPath());
            self::assertSame($configObject, $context->getRootConfig());
            self::assertSame($childConfigObject, $context->getConfig());
            self::assertEquals($sourceDataItem, $context->getResult());

            $context->setResult($processedDataItem);
        };

        $assertion = function () use ($childConfigObject, $processedDataItem, $sourceDataItem, $handlerType) {
            $getter = 'getPostSerialize' . ucfirst($handlerType) . 'Handler';
            $childHandler = $childConfigObject->{$getter}();
            self::assertEquals(
                $processedDataItem,
                $childHandler($sourceDataItem, ['sharedData' => $this->createMock(ParameterBagInterface::class)])
            );
        };

        return [$assertion, $expectation];
    }
}
