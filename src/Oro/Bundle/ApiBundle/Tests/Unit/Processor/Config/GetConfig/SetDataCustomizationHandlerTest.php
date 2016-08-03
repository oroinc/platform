<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\GetConfig\SetDataCustomizationHandler;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;

class SetDataCustomizationHandlerTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $customizationProcessor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var SetDataCustomizationHandler */
    protected $processor;

    /** @var int */
    protected $customizationProcessorCallIndex;

    protected function setUp()
    {
        parent::setUp();

        $this->customizationProcessorCallIndex = 0;

        $this->customizationProcessor = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\Processor\CustomizeLoadedDataProcessor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper         = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new SetDataCustomizationHandler(
            $this->customizationProcessor,
            $this->doctrineHelper
        );
    }

    public function testProcessForEmptyConfig()
    {
        $config = [];

        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntityClass');

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig([], $configObject);
    }

    public function testProcessForNotCompletedConfig()
    {
        $config = [
            'exclusion_policy' => 'none',
            'fields'           => [
                'field1' => null
            ]
        ];

        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntityClass');

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

    public function testProcessForNotManageableEntity()
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

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertNotNull(
            $configObject->getPostSerializeHandler()
        );
        $this->assertNull(
            $configObject
                ->getField('field1')
                ->getTargetEntity()
        );
        $this->assertNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getPostSerializeHandler()
        );

        $assert = $this->getRootHandlerAssertion($configObject);
        $assert();
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForManageableEntity()
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

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('hasAssociation')
            ->willReturnMap([['field2', true]]);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationTargetClass')
            ->with('field2')
            ->willReturn('Test\Field2Target');

        $field2TargetEntityMetadata = $this->getClassMetadataMock('Test\Field2Target');
        $field2TargetEntityMetadata->expects($this->any())
            ->method('hasAssociation')
            ->willReturnMap([['field22', true]]);
        $field2TargetEntityMetadata->expects($this->any())
            ->method('getAssociationTargetClass')
            ->with('field22')
            ->willReturn('Test\Field22Target');

        $field22TargetEntityMetadata = $this->getClassMetadataMock('Test\Field22Target');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->exactly(3))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                    ['Test\Field2Target', true, $field2TargetEntityMetadata],
                    ['Test\Field22Target', true, $field22TargetEntityMetadata],
                ]
            );

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertNotNull(
            $configObject->getPostSerializeHandler()
        );
        $this->assertNull(
            $configObject
                ->getField('field1')
                ->getTargetEntity()
        );
        $this->assertNotNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getPostSerializeHandler()
        );
        $this->assertNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field21')
                ->getTargetEntity()
        );
        $this->assertNotNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field22')
                ->getTargetEntity()
                ->getPostSerializeHandler()
        );
        $this->assertNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field22')
                ->getTargetEntity()
                ->getField('field221')
                ->getTargetEntity()
        );

        $rootAssert    = $this->getRootHandlerAssertion($configObject);
        $field2Assert  = $this->getChildHandlerAssertion(
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
    public function testProcessForManageableEntityAndAssociationsWithPropertyPath()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'exclusion_policy' => 'all',
                    'property_path'    => 'realField2',
                    'fields'           => [
                        'field21' => null,
                        'field22' => [
                            'exclusion_policy' => 'all',
                            'property_path'    => 'realField22',
                            'fields'           => [
                                'field221' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->any())
            ->method('hasAssociation')
            ->willReturnMap([['realField2', true]]);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationTargetClass')
            ->with('realField2')
            ->willReturn('Test\Field2Target');

        $field2TargetEntityMetadata = $this->getClassMetadataMock('Test\Field2Target');
        $field2TargetEntityMetadata->expects($this->any())
            ->method('hasAssociation')
            ->willReturnMap([['realField22', true]]);
        $field2TargetEntityMetadata->expects($this->any())
            ->method('getAssociationTargetClass')
            ->with('realField22')
            ->willReturn('Test\Field22Target');

        $field22TargetEntityMetadata = $this->getClassMetadataMock('Test\Field22Target');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->exactly(3))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                    ['Test\Field2Target', true, $field2TargetEntityMetadata],
                    ['Test\Field22Target', true, $field22TargetEntityMetadata],
                ]
            );

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertNotNull(
            $configObject->getPostSerializeHandler()
        );
        $this->assertNull(
            $configObject
                ->getField('field1')
                ->getTargetEntity()
        );
        $this->assertNotNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getPostSerializeHandler()
        );
        $this->assertNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field21')
                ->getTargetEntity()
        );
        $this->assertNotNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field22')
                ->getTargetEntity()
                ->getPostSerializeHandler()
        );
        $this->assertNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field22')
                ->getTargetEntity()
                ->getField('field221')
                ->getTargetEntity()
        );

        $rootAssert    = $this->getRootHandlerAssertion($configObject);
        $field2Assert  = $this->getChildHandlerAssertion(
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
    public function testProcessForManageableEntityAndAssociationsWithPropertyPathToChildEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'exclusion_policy' => 'all',
                    'property_path'    => 'field22.field221',
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

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);

        $field2TargetEntityMetadata = $this->getClassMetadataMock('Test\Field2Target');
        $field2TargetEntityMetadata->expects($this->any())
            ->method('hasAssociation')
            ->willReturnMap([['field22', true]]);
        $field2TargetEntityMetadata->expects($this->any())
            ->method('getAssociationTargetClass')
            ->with('field22')
            ->willReturn('Test\Field22Target');

        $field22TargetEntityMetadata = $this->getClassMetadataMock('Test\Field22Target');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityMetadataForClass')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, true, $rootEntityMetadata],
                    ['Test\Field22Target', true, $field22TargetEntityMetadata],
                ]
            );
        $this->doctrineHelper->expects($this->once())
            ->method('findEntityMetadataByPath')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, ['field22'], $field2TargetEntityMetadata],
                ]
            );

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertNotNull(
            $configObject->getPostSerializeHandler()
        );
        $this->assertNull(
            $configObject
                ->getField('field1')
                ->getTargetEntity()
        );
        $this->assertNotNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getPostSerializeHandler()
        );
        $this->assertNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field21')
                ->getTargetEntity()
        );
        $this->assertNotNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field22')
                ->getTargetEntity()
                ->getPostSerializeHandler()
        );
        $this->assertNull(
            $configObject
                ->getField('field2')
                ->getTargetEntity()
                ->getField('field22')
                ->getTargetEntity()
                ->getField('field221')
                ->getTargetEntity()
        );

        $rootAssert    = $this->getRootHandlerAssertion($configObject);
        $field2Assert  = $this->getChildHandlerAssertion(
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
     * @param EntityDefinitionConfig $configObject
     *
     * @return callable
     */
    protected function getRootHandlerAssertion(EntityDefinitionConfig $configObject)
    {
        $sourceDataItem    = ['source data'];
        $processedDataItem = ['processed data'];
        $this->customizationProcessor->expects($this->at($this->customizationProcessorCallIndex++))
            ->method('process')
            ->willReturnCallback(
                function (CustomizeLoadedDataContext $context) use (
                    $sourceDataItem,
                    $processedDataItem,
                    $configObject
                ) {
                    $this->assertEquals($this->context->getVersion(), $context->getVersion());
                    $this->assertEquals($this->context->getRequestType(), $context->getRequestType());
                    $this->assertEquals($this->context->getClassName(), $context->getClassName());
                    $this->assertSame($configObject, $context->getConfig());
                    $this->assertEquals($sourceDataItem, $context->getResult());

                    $context->setResult($processedDataItem);
                }
            );

        return function () use ($configObject, $processedDataItem, $sourceDataItem) {
            $rootHandler = $configObject->getPostSerializeHandler();
            $this->assertEquals(
                $processedDataItem,
                call_user_func($rootHandler, $sourceDataItem)
            );
        };
    }

    /**
     * @param EntityDefinitionConfig $configObject
     * @param EntityDefinitionConfig $childConfigObject
     * @param string                 $childEntityClass
     * @param string                 $fieldPath
     *
     * @return callable
     */
    protected function getChildHandlerAssertion(
        EntityDefinitionConfig $configObject,
        EntityDefinitionConfig $childConfigObject,
        $childEntityClass,
        $fieldPath
    ) {
        $sourceDataItem    = ['source data'];
        $processedDataItem = ['processed data'];
        $this->customizationProcessor->expects($this->at($this->customizationProcessorCallIndex++))
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
                    $this->assertEquals($this->context->getVersion(), $context->getVersion());
                    $this->assertEquals($this->context->getRequestType(), $context->getRequestType());
                    $this->assertEquals($this->context->getClassName(), $context->getRootClassName());
                    $this->assertEquals($childEntityClass, $context->getClassName());
                    $this->assertEquals($fieldPath, $context->getPropertyPath());
                    $this->assertSame($configObject, $context->getRootConfig());
                    $this->assertSame($childConfigObject, $context->getConfig());
                    $this->assertEquals($sourceDataItem, $context->getResult());

                    $context->setResult($processedDataItem);
                }
            );

        return function () use ($childConfigObject, $processedDataItem, $sourceDataItem) {
            $childHandler = $childConfigObject->getPostSerializeHandler();
            $this->assertEquals(
                $processedDataItem,
                call_user_func($childHandler, $sourceDataItem)
            );
        };
    }
}
