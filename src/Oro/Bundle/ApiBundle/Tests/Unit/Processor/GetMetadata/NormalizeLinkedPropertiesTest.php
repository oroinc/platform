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

    /**
     * @param string      $fieldName
     * @param string|null $dataType
     *
     * @return FieldMetadata
     */
    protected function createFieldMetadata($fieldName, $dataType = null)
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName($fieldName);
        if ($dataType) {
            $fieldMetadata->setDataType($dataType);
        }
        $fieldMetadata->setIsNullable(false);

        return $fieldMetadata;
    }

    /**
     * @param string        $associationName
     * @param string        $targetClass
     * @param bool|null     $isCollection
     * @param string|null   $dataType
     * @param string[]|null $acceptableTargetClasses
     *
     * @return AssociationMetadata
     */
    protected function createAssociationMetadata(
        $associationName,
        $targetClass,
        $isCollection = null,
        $dataType = null,
        $acceptableTargetClasses = null
    ) {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName($associationName);
        $associationMetadata->setTargetClassName($targetClass);
        if (null !== $isCollection) {
            $associationMetadata->setIsCollection($isCollection);
        }
        if (null !== $dataType) {
            $associationMetadata->setDataType($dataType);
        }
        if (null !== $acceptableTargetClasses) {
            $associationMetadata->setAcceptableTargetClassNames($acceptableTargetClasses);
        }
        $associationMetadata->setIsNullable(false);

        return $associationMetadata;
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
        $metadata->addField($this->createFieldMetadata('field1'));
        $metadata->addField($this->createFieldMetadata('field2'));
        $metadata->addAssociation(
            $this->createAssociationMetadata('association3', 'Test\Association3Target')
        );
        $metadata->addAssociation(
            $this->createAssociationMetadata('association411', 'Test\Association411Target')
        );

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
        $expectedMetadata->addField($this->createFieldMetadata('field1'));
        $expectedMetadata->addField($this->createFieldMetadata('field2'));
        $expectedMetadata->addAssociation(
            $this->createAssociationMetadata('association3', 'Test\Association3Target')
        );
        $expectedMetadata->addField($this->createFieldMetadata('field5', 'string'));
        $expectedAssociation4 = $this->createAssociationMetadata(
            'association4',
            'Test\Association411Target',
            false,
            'integer',
            ['Test\Association411Target']
        );
        $expectedAssociation4->setIsNullable(true);
        $expectedMetadata->addAssociation($expectedAssociation4);
        $expectedMetadata->addAssociation(
            $this->createAssociationMetadata('association411', 'Test\Association411Target')
        );

        $this->assertEquals($expectedMetadata, $this->context->getResult());
    }
}
