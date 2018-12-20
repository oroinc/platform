<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\GetConfig\SetDataCustomizationHandler;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\Handler\AssociationHandler;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\Handler\EntityHandler;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedDataProcessor;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;

class SetDataCustomizationHandlerTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|CustomizeLoadedDataProcessor */
    private $customizationProcessor;

    /** @var SetDataCustomizationHandler */
    private $processor;

    /** @var int */
    private $customizationProcessorCallIndex;

    protected function setUp()
    {
        parent::setUp();

        $this->customizationProcessorCallIndex = 0;

        $this->customizationProcessor = $this->createMock(CustomizeLoadedDataProcessor::class);

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

        $assert = $this->getRootHandlerAssertion($configObject);
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
        $this->context->setTargetAction(ApiActions::GET_LIST);
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

        $rootAssert = $this->getRootHandlerAssertion($configObject);
        $rootCollectionAssert = $this->getRootHandlerAssertion($configObject, 'collection');
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

        $rootAssert = $this->getRootHandlerAssertion($configObject);
        $field2Assert = $this->getChildHandlerAssertion(
            $configObject,
            $configObject->getField('field2')->getTargetEntity(),
            'Test\Field2Target',
            'field2'
        );
        $field22Assert = $this->getChildHandlerAssertion(
            $configObject,
            $configObject->getField('field2')->getTargetEntity()->getField('field22')->getTargetEntity(),
            'Test\Field22Target',
            'field2.field22'
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

        $rootAssert = $this->getRootHandlerAssertion($configObject);
        $field2Assert = $this->getChildHandlerAssertion(
            $configObject,
            $configObject->getField('field2')->getTargetEntity(),
            'Test\Field2Target',
            'field2'
        );
        $field2CollectionAssert = $this->getChildHandlerAssertion(
            $configObject,
            $configObject->getField('field2')->getTargetEntity(),
            'Test\Field2Target',
            'field2',
            'collection'
        );
        $field22Assert = $this->getChildHandlerAssertion(
            $configObject,
            $configObject->getField('field2')->getTargetEntity()->getField('field22')->getTargetEntity(),
            'Test\Field22Target',
            'field2.field22'
        );
        $field22CollectionAssert = $this->getChildHandlerAssertion(
            $configObject,
            $configObject->getField('field2')->getTargetEntity()->getField('field22')->getTargetEntity(),
            'Test\Field22Target',
            'field2.field22',
            'collection'
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

        $rootAssert = $this->getRootHandlerAssertion($configObject);
        $field2Assert = $this->getChildHandlerAssertion(
            $configObject,
            $configObject->getField('field2')->getTargetEntity(),
            'Test\Field2Target',
            'field2'
        );
        $field22Assert = $this->getChildHandlerAssertion(
            $configObject,
            $configObject->getField('field2')->getTargetEntity()->getField('field22')->getTargetEntity(),
            'Test\Field22Target',
            'field2.field22'
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

        $rootAssert = $this->getRootHandlerAssertion($configObject);
        $rootAssert();
    }

    /**
     * @param EntityDefinitionConfig $configObject
     * @param string                 $handlerType
     *
     * @return callable
     */
    private function getRootHandlerAssertion(EntityDefinitionConfig $configObject, $handlerType = '')
    {
        $sourceDataItem = ['source data'];
        $processedDataItem = ['processed data'];
        $this->customizationProcessor->expects(self::at($this->customizationProcessorCallIndex++))
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) use (
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
                }
            );

        return function () use ($configObject, $processedDataItem, $sourceDataItem, $handlerType) {
            $getter = 'getPostSerialize' . ucfirst($handlerType) . 'Handler';
            $rootHandler = $configObject->{$getter}();
            self::assertEquals(
                $processedDataItem,
                $rootHandler($sourceDataItem)
            );
        };
    }

    /**
     * @param EntityDefinitionConfig $configObject
     * @param EntityDefinitionConfig $childConfigObject
     * @param string                 $childEntityClass
     * @param string                 $fieldPath
     * @param string                 $handlerType
     *
     * @return callable
     */
    private function getChildHandlerAssertion(
        EntityDefinitionConfig $configObject,
        EntityDefinitionConfig $childConfigObject,
        $childEntityClass,
        $fieldPath,
        $handlerType = ''
    ) {
        $sourceDataItem = ['source data'];
        $processedDataItem = ['processed data'];
        $this->customizationProcessor->expects(self::at($this->customizationProcessorCallIndex++))
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) use (
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
                }
            );

        return function () use ($childConfigObject, $processedDataItem, $sourceDataItem, $handlerType) {
            $getter = 'getPostSerialize' . ucfirst($handlerType) . 'Handler';
            $childHandler = $childConfigObject->{$getter}();
            self::assertEquals(
                $processedDataItem,
                $childHandler($sourceDataItem)
            );
        };
    }
}
