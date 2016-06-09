<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\NormalizeLinkedProperties;

class NormalizeLinkedPropertiesTest extends MetadataProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var NormalizeLinkedProperties */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new NormalizeLinkedProperties(
            $this->doctrineHelper,
            new EntityMetadataFactory($this->doctrineHelper)
        );
    }

    public function testProcessWithoutMetadata()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntityClass');

        $this->processor->process($this->context);
    }

    public function testProcessWithoutConfig()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntityClass');

        $this->context->setResult(new EntityMetadata());
        $this->processor->process($this->context);
    }

    public function testProcessForNotManageableEntity()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->context->setConfig($this->createConfigObject([]));
        $this->context->setResult(new EntityMetadata());
        $this->processor->process($this->context);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcess()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'       => null,
                'field2'       => [
                    'property_path' => 'realField2'
                ],
                'association3' => [
                    'property_path' => 'association31.association311'
                ],
                'association4' => [
                    'property_path' => 'association41.association411'
                ],
                'field5'       => [
                    'property_path' => 'association51.field511'
                ],
                'field6'       => [
                    'property_path' => 'field61.field611'
                ],
            ]
        ];

        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS_NAME);
        $field1 = new FieldMetadata();
        $field1->setName('field1');
        $field1->setIsNullable(false);
        $metadata->addField($field1);
        $field2 = new FieldMetadata();
        $field2->setName('field2');
        $field2->setIsNullable(false);
        $metadata->addField($field2);
        $association3 = new AssociationMetadata();
        $association3->setTargetClassName('Test\Association3Target');
        $association3->setName('association3');
        $association3->setIsNullable(false);
        $metadata->addAssociation($association3);
        $association411 = new AssociationMetadata();
        $association411->setTargetClassName('Test\Association411Target');
        $association411->setName('association411');
        $association411->setIsNullable(false);
        $metadata->addAssociation($association411);

        $association41ClassMetadata = $this->getClassMetadataMock('Test\Association41Target');
        $association41ClassMetadata->expects($this->once())
            ->method('hasAssociation')
            ->with('association411')
            ->willReturn(true);
        $association41ClassMetadata->expects($this->once())
            ->method('getAssociationTargetClass')
            ->with('association411')
            ->willReturn('Test\Association411Target');
        $association41ClassMetadata->expects($this->once())
            ->method('isCollectionValuedAssociation')
            ->with('association411')
            ->willReturn(false);

        $association411ClassMetadata = $this->getClassMetadataMock('Test\Association411Target');
        $association411ClassMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $association411ClassMetadata->expects($this->once())
            ->method('getTypeOfField')
            ->with('id')
            ->willReturn('integer');

        $association51ClassMetadata = $this->getClassMetadataMock('Test\Association51Target');
        $association51ClassMetadata->expects($this->once())
            ->method('hasAssociation')
            ->with('field511')
            ->willReturn(false);
        $association51ClassMetadata->expects($this->once())
            ->method('getTypeOfField')
            ->with('field511')
            ->willReturn('string');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->exactly(3))
            ->method('findEntityMetadataByPath')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, ['association41'], $association41ClassMetadata],
                    [self::TEST_CLASS_NAME, ['association51'], $association51ClassMetadata],
                    [self::TEST_CLASS_NAME, ['field61'], null],
                ]
            );
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with('Test\Association411Target')
            ->willReturn($association411ClassMetadata);

        $this->context->setConfig($this->createConfigObject($config));
        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        $expectedMetadata = new EntityMetadata();
        $expectedMetadata->setClassName(self::TEST_CLASS_NAME);
        $expectedField1 = new FieldMetadata();
        $expectedField1->setName('field1');
        $expectedField1->setIsNullable(false);
        $expectedMetadata->addField($expectedField1);
        $expectedField2 = new FieldMetadata();
        $expectedField2->setName('field2');
        $expectedField2->setIsNullable(false);
        $expectedMetadata->addField($expectedField2);
        $expectedAssociation3 = new AssociationMetadata();
        $expectedAssociation3->setTargetClassName('Test\Association3Target');
        $expectedAssociation3->setName('association3');
        $expectedAssociation3->setIsNullable(false);
        $expectedMetadata->addAssociation($expectedAssociation3);
        $expectedField5 = new FieldMetadata();
        $expectedField5->setName('field5');
        $expectedField5->setDataType('string');
        $expectedField5->setIsNullable(false);
        $expectedMetadata->addField($expectedField5);
        $expectedAssociation4 = new AssociationMetadata();
        $expectedAssociation4->setTargetClassName('Test\Association411Target');
        $expectedAssociation4->setAcceptableTargetClassNames(['Test\Association411Target']);
        $expectedAssociation4->setName('association4');
        $expectedAssociation4->setDataType('integer');
        $expectedAssociation4->setIsNullable(true);
        $expectedAssociation4->setIsCollection(false);
        $expectedMetadata->addAssociation($expectedAssociation4);
        $expectedAssociation411 = new AssociationMetadata();
        $expectedAssociation411->setTargetClassName('Test\Association411Target');
        $expectedAssociation411->setName('association411');
        $expectedAssociation411->setIsNullable(false);
        $expectedMetadata->addAssociation($expectedAssociation411);

        $this->assertEquals($expectedMetadata, $this->context->getResult());
    }
}
