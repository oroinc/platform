<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\ExternalLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Metadata\TargetMetadataAccessorInterface;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class EntityMetadataTest extends TestCase
{
    public function testClone(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        $entityMetadata->setInheritedType(true);
        $entityMetadata->setHasIdentifierGenerator(true);
        $entityMetadata->setIdentifierFieldNames(['field1']);
        $entityMetadata->set('test_scalar', 'value');
        $objValue = new \stdClass();
        $objValue->someProp = 123;
        $entityMetadata->set('test_object', $objValue);
        $metaProperty1 = new MetaPropertyMetadata('metaProperty1');
        $entityMetadata->addMetaProperty($metaProperty1);
        $field1 = new FieldMetadata('field1');
        $entityMetadata->addField($field1);
        $association1 = new AssociationMetadata('association1');
        $entityMetadata->addAssociation($association1);
        $entityMetadata->addLink('link1', new ExternalLinkMetadata('url1'));
        $entityMetadata->setHints(['HINT_TEST']);

        $entityMetadataClone = clone $entityMetadata;

        self::assertEquals($entityMetadata, $entityMetadataClone);
        self::assertNotSame($objValue, $entityMetadataClone->get('test_object'));
        self::assertNotSame($metaProperty1, $entityMetadataClone->getMetaProperty('metaProperty1'));
        self::assertNotSame($field1, $entityMetadataClone->getField('field1'));
        self::assertNotSame($association1, $entityMetadataClone->getAssociation('association1'));
    }

    public function testCloneForEmptyEntityMetadata(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');

        $entityMetadataClone = clone $entityMetadata;

        self::assertEquals($entityMetadata, $entityMetadataClone);
    }

    public function testToArray(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        $entityMetadata->setInheritedType(true);
        $entityMetadata->setHasIdentifierGenerator(true);
        $entityMetadata->setIdentifierFieldNames(['field1']);
        $entityMetadata->set('test_scalar', 'value');
        $objValue = new \stdClass();
        $objValue->someProp = 123;
        $entityMetadata->set('test_object', $objValue);
        $entityMetadata->addMetaProperty(new MetaPropertyMetadata('metaProperty1', 'testDataType'));
        $field1 = new FieldMetadata('field1');
        $field1->setDataType('testDataType');
        $entityMetadata->addField($field1);
        $association1 = new AssociationMetadata('association1');
        $association1->setDataType('testDataType');
        $entityMetadata->addAssociation($association1);
        $entityMetadata->addLink('link1', new ExternalLinkMetadata('url1'));
        $entityMetadata->setHints(['HINT_TEST']);

        self::assertEquals(
            [
                'class'                    => 'Test\Class',
                'inherited'                => true,
                'has_identifier_generator' => true,
                'identifiers'              => ['field1'],
                'test_scalar'              => 'value',
                'test_object'              => $objValue,
                'hints'                    => ['HINT_TEST'],
                'meta_properties'          => [
                    'metaProperty1' => [
                        'data_type' => 'testDataType'
                    ]
                ],
                'links'                    => [
                    'link1' => [
                        'url_template' => 'url1'
                    ]
                ],
                'fields'                   => [
                    'field1' => [
                        'data_type' => 'testDataType'
                    ]
                ],
                'associations'             => [
                    'association1' => [
                        'data_type'        => 'testDataType',
                        'nullable'         => false,
                        'collapsed'        => false,
                        'association_type' => null,
                        'collection'       => false
                    ]
                ]
            ],
            $entityMetadata->toArray()
        );
    }

    public function testToArrayForEmptyEntityMetadata(): void
    {
        $associationMetadata = new EntityMetadata('Test\Class');

        self::assertEquals(
            ['class' => 'Test\Class'],
            $associationMetadata->toArray()
        );
    }

    public function testClassName(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        self::assertEquals('Test\Class', $entityMetadata->getClassName());

        $entityMetadata->setClassName('Test\AnotherClass');
        self::assertEquals('Test\AnotherClass', $entityMetadata->getClassName());
    }

    public function testIdentifierFieldNames(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        self::assertEmpty($entityMetadata->getIdentifierFieldNames());
        self::assertFalse($entityMetadata->hasIdentifierFields());

        $entityMetadata->setIdentifierFieldNames(['id']);
        self::assertEquals(['id'], $entityMetadata->getIdentifierFieldNames());
        self::assertTrue($entityMetadata->hasIdentifierFields());
    }

    public function testInheritedType(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        self::assertFalse($entityMetadata->isInheritedType());

        $entityMetadata->setInheritedType(true);
        self::assertTrue($entityMetadata->isInheritedType());

        $entityMetadata->setInheritedType(false);
        self::assertFalse($entityMetadata->isInheritedType());
    }

    public function testIdentifierGenerator(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        self::assertFalse($entityMetadata->hasIdentifierGenerator());

        $entityMetadata->setHasIdentifierGenerator(true);
        self::assertTrue($entityMetadata->hasIdentifierGenerator());

        $entityMetadata->setHasIdentifierGenerator(false);
        self::assertFalse($entityMetadata->hasIdentifierGenerator());
    }

    public function testSetEntityMetadataFullMode(): void
    {
        $targetMetadataAccessor = $this->createMock(TargetMetadataAccessorInterface::class);
        $targetMetadataAccessor->expects(self::once())
            ->method('setFullMode')
            ->with(true);

        $associationMetadata = $this->createMock(AssociationMetadata::class);
        $associationMetadata->expects(self::once())
            ->method('setTargetMetadataFullMode')
            ->with(true);

        $entityMetadata = new EntityMetadata('Test\Class');
        $entityMetadata->setTargetMetadataAccessor($targetMetadataAccessor);
        $entityMetadata->addAssociation($associationMetadata);

        $entityMetadata->setEntityMetadataFullMode(true);
    }

    public function testSetEntityMetadataFullModeWhenTargetMetadataAccessorIsNotSet(): void
    {
        $associationMetadata = $this->createMock(AssociationMetadata::class);
        $associationMetadata->expects(self::once())
            ->method('setTargetMetadataFullMode')
            ->with(true);

        $entityMetadata = new EntityMetadata('Test\Class');
        $entityMetadata->addAssociation($associationMetadata);

        $entityMetadata->setEntityMetadataFullMode(true);
    }

    public function testGetEntityMetadata(): void
    {
        $anotherEntityMetadata = new EntityMetadata('Test\AnotherClass');

        $targetMetadataAccessor = $this->createMock(TargetMetadataAccessorInterface::class);
        $targetMetadataAccessor->expects(self::once())
            ->method('getTargetMetadata')
            ->with('Test\AnotherClass', self::isNull())
            ->willReturn($anotherEntityMetadata);

        $entityMetadata = new EntityMetadata('Test\Class');
        $entityMetadata->setTargetMetadataAccessor($targetMetadataAccessor);

        self::assertSame($anotherEntityMetadata, $entityMetadata->getEntityMetadata('Test\AnotherClass'));
    }

    public function testGetEntityMetadataWhenEntityMetadataNotFound(): void
    {
        $targetMetadataAccessor = $this->createMock(TargetMetadataAccessorInterface::class);
        $targetMetadataAccessor->expects(self::once())
            ->method('getTargetMetadata')
            ->with('Test\AnotherClass', self::isNull())
            ->willReturn(null);

        $entityMetadata = new EntityMetadata('Test\Class');
        $entityMetadata->setTargetMetadataAccessor($targetMetadataAccessor);

        self::assertNull($entityMetadata->getEntityMetadata('Test\AnotherClass'));
    }

    public function testGetEntityMetadataWhenClassNameEqualToCurrentMetadataClassName(): void
    {
        $targetMetadataAccessor = $this->createMock(TargetMetadataAccessorInterface::class);
        $targetMetadataAccessor->expects(self::never())
            ->method('getTargetMetadata');

        $entityMetadata = new EntityMetadata('Test\Class');
        $entityMetadata->setTargetMetadataAccessor($targetMetadataAccessor);

        self::assertSame($entityMetadata, $entityMetadata->getEntityMetadata('Test\Class'));
    }

    public function testGetEntityMetadataWhenClassNameEqualsToCurrentMetadataClassNameButFullModeIsSet(): void
    {
        $fullEntityMetadata = new EntityMetadata('Test\Class');

        $targetMetadataAccessor = $this->createMock(TargetMetadataAccessorInterface::class);
        $targetMetadataAccessor->expects(self::once())
            ->method('isFullMode')
            ->willReturn(true);
        $targetMetadataAccessor->expects(self::once())
            ->method('getTargetMetadata')
            ->with('Test\Class', self::isNull())
            ->willReturn($fullEntityMetadata);

        $entityMetadata = new EntityMetadata('Test\Class');
        $entityMetadata->setTargetMetadataAccessor($targetMetadataAccessor);

        self::assertSame($fullEntityMetadata, $entityMetadata->getEntityMetadata('Test\Class'));
    }

    public function testGetEntityMetadataWhenTargetMetadataAccessorIsNotSet(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        self::assertSame($entityMetadata, $entityMetadata->getEntityMetadata('Test\AnotherClass'));
    }

    public function testGetPropertyPath(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        $entityMetadata->addMetaProperty(new MetaPropertyMetadata('property1'));
        $entityMetadata->addMetaProperty(new MetaPropertyMetadata('renamedProperty2'))
            ->setPropertyPath('property2');
        $entityMetadata->addField(new FieldMetadata('field1'));
        $entityMetadata->addField(new FieldMetadata('renamedField2'))
            ->setPropertyPath('field2');
        $entityMetadata->addAssociation(new AssociationMetadata('association1'));
        $entityMetadata->addAssociation(new AssociationMetadata('renamedAssociation2'))
            ->setPropertyPath('association2');

        self::assertEquals('property1', $entityMetadata->getPropertyPath('property1'));
        self::assertEquals('property2', $entityMetadata->getPropertyPath('renamedProperty2'));
        self::assertNull($entityMetadata->getPropertyPath('property2'));

        self::assertEquals('field1', $entityMetadata->getPropertyPath('field1'));
        self::assertEquals('field2', $entityMetadata->getPropertyPath('renamedField2'));
        self::assertNull($entityMetadata->getPropertyPath('field2'));

        self::assertEquals('association1', $entityMetadata->getPropertyPath('association1'));
        self::assertEquals('association2', $entityMetadata->getPropertyPath('renamedAssociation2'));
        self::assertNull($entityMetadata->getPropertyPath('association2'));

        self::assertNull($entityMetadata->getPropertyPath('unknown'));
    }

    public function testMetaProperties(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        self::assertCount(0, $entityMetadata->getMetaProperties());
        self::assertFalse($entityMetadata->hasProperty('unknown'));
        self::assertFalse($entityMetadata->hasMetaProperty('unknown'));
        self::assertNull($entityMetadata->getProperty('unknown'));
        self::assertNull($entityMetadata->getMetaProperty('unknown'));

        $property1 = new MetaPropertyMetadata('property1');
        self::assertSame($property1, $entityMetadata->addMetaProperty($property1));
        $property2 = new MetaPropertyMetadata('property2');
        self::assertSame($property2, $entityMetadata->addMetaProperty($property2));
        self::assertCount(2, $entityMetadata->getMetaProperties());

        self::assertTrue($entityMetadata->hasProperty('property1'));
        self::assertTrue($entityMetadata->hasMetaProperty('property1'));
        self::assertSame($property1, $entityMetadata->getProperty('property1'));
        self::assertSame($property1, $entityMetadata->getMetaProperty('property1'));

        $entityMetadata->removeMetaProperty('property1');
        self::assertCount(1, $entityMetadata->getMetaProperties());
        self::assertFalse($entityMetadata->hasProperty('property1'));
        self::assertFalse($entityMetadata->hasMetaProperty('property1'));

        $entityMetadata->removeProperty('property2');
        self::assertCount(0, $entityMetadata->getMetaProperties());
        self::assertFalse($entityMetadata->hasProperty('property2'));
        self::assertFalse($entityMetadata->hasMetaProperty('property2'));
    }

    public function testFields(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        self::assertCount(0, $entityMetadata->getFields());
        self::assertFalse($entityMetadata->hasProperty('unknown'));
        self::assertFalse($entityMetadata->hasField('unknown'));
        self::assertNull($entityMetadata->getProperty('unknown'));
        self::assertNull($entityMetadata->getField('unknown'));

        $field1 = new FieldMetadata('field1');
        self::assertSame($field1, $entityMetadata->addField($field1));
        $field2 = new FieldMetadata('field2');
        self::assertSame($field2, $entityMetadata->addField($field2));
        self::assertCount(2, $entityMetadata->getFields());

        self::assertTrue($entityMetadata->hasProperty('field1'));
        self::assertTrue($entityMetadata->hasField('field1'));
        self::assertSame($field1, $entityMetadata->getProperty('field1'));
        self::assertSame($field1, $entityMetadata->getField('field1'));

        $entityMetadata->removeField('field1');
        self::assertCount(1, $entityMetadata->getFields());
        self::assertFalse($entityMetadata->hasProperty('field1'));
        self::assertFalse($entityMetadata->hasField('field1'));

        $entityMetadata->removeProperty('field2');
        self::assertCount(0, $entityMetadata->getFields());
        self::assertFalse($entityMetadata->hasProperty('field2'));
        self::assertFalse($entityMetadata->hasField('field2'));
    }

    public function testAssociations(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        self::assertCount(0, $entityMetadata->getAssociations());
        self::assertFalse($entityMetadata->hasProperty('unknown'));
        self::assertFalse($entityMetadata->hasAssociation('unknown'));
        self::assertNull($entityMetadata->getProperty('unknown'));
        self::assertNull($entityMetadata->getAssociation('unknown'));

        $association1 = new AssociationMetadata('association1');
        self::assertSame($association1, $entityMetadata->addAssociation($association1));
        $association2 = new AssociationMetadata('association2');
        self::assertSame($association2, $entityMetadata->addAssociation($association2));
        self::assertCount(2, $entityMetadata->getAssociations());

        self::assertTrue($entityMetadata->hasProperty('association1'));
        self::assertTrue($entityMetadata->hasAssociation('association1'));
        self::assertSame($association1, $entityMetadata->getProperty('association1'));
        self::assertSame($association1, $entityMetadata->getAssociation('association1'));

        $entityMetadata->removeAssociation('association1');
        self::assertCount(1, $entityMetadata->getAssociations());
        self::assertFalse($entityMetadata->hasProperty('association1'));
        self::assertFalse($entityMetadata->hasAssociation('association1'));

        $entityMetadata->removeProperty('association2');
        self::assertCount(0, $entityMetadata->getAssociations());
        self::assertFalse($entityMetadata->hasProperty('association2'));
        self::assertFalse($entityMetadata->hasAssociation('association2'));
    }

    public function testRenameMetaProperty(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        $entityMetadata->addMetaProperty(new MetaPropertyMetadata('property1', 'string'));

        $entityMetadata->renameMetaProperty('property1', 'newProperty1');
        self::assertFalse($entityMetadata->hasMetaProperty('property1'));
        self::assertFalse($entityMetadata->hasProperty('property1'));
        self::assertTrue($entityMetadata->hasMetaProperty('newProperty1'));
        self::assertTrue($entityMetadata->hasProperty('newProperty1'));
        self::assertEquals(
            new MetaPropertyMetadata('newProperty1', 'string'),
            $entityMetadata->getMetaProperty('newProperty1')
        );
    }

    public function testRenameMetaPropertyViaProperty(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        $entityMetadata->addMetaProperty(new MetaPropertyMetadata('property1', 'string'));

        $entityMetadata->renameProperty('property1', 'newProperty1');
        self::assertFalse($entityMetadata->hasMetaProperty('property1'));
        self::assertFalse($entityMetadata->hasProperty('property1'));
        self::assertTrue($entityMetadata->hasMetaProperty('newProperty1'));
        self::assertTrue($entityMetadata->hasProperty('newProperty1'));
        self::assertEquals(
            new MetaPropertyMetadata('newProperty1', 'string'),
            $entityMetadata->getMetaProperty('newProperty1')
        );
    }

    public function testRenameField(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        $field1 = $entityMetadata->addField(new FieldMetadata('field1'));
        $field1->setDataType('string');

        $entityMetadata->renameField('field1', 'newField1');
        self::assertFalse($entityMetadata->hasField('field1'));
        self::assertFalse($entityMetadata->hasProperty('field1'));
        self::assertTrue($entityMetadata->hasField('newField1'));
        self::assertTrue($entityMetadata->hasProperty('newField1'));
        $expectedNewField1 = new FieldMetadata('newField1');
        $expectedNewField1->setDataType('string');
        self::assertEquals(
            $expectedNewField1,
            $entityMetadata->getField('newField1')
        );
    }

    public function testRenameFieldViaProperty(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        $field1 = $entityMetadata->addField(new FieldMetadata('field1'));
        $field1->setDataType('string');

        $entityMetadata->renameProperty('field1', 'newField1');
        self::assertFalse($entityMetadata->hasField('field1'));
        self::assertFalse($entityMetadata->hasProperty('field1'));
        self::assertTrue($entityMetadata->hasField('newField1'));
        self::assertTrue($entityMetadata->hasProperty('newField1'));
        $expectedNewField1 = new FieldMetadata('newField1');
        $expectedNewField1->setDataType('string');
        self::assertEquals(
            $expectedNewField1,
            $entityMetadata->getField('newField1')
        );
    }

    public function testRenameAssociation(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        $association1 = $entityMetadata->addAssociation(new AssociationMetadata('association1'));
        $association1->setDataType('string');

        $entityMetadata->renameAssociation('association1', 'newAssociation1');
        self::assertFalse($entityMetadata->hasAssociation('association1'));
        self::assertFalse($entityMetadata->hasProperty('association1'));
        self::assertTrue($entityMetadata->hasAssociation('newAssociation1'));
        self::assertTrue($entityMetadata->hasProperty('newAssociation1'));
        $expectedNewAssociation1 = new AssociationMetadata('newAssociation1');
        $expectedNewAssociation1->setDataType('string');
        self::assertEquals(
            $expectedNewAssociation1,
            $entityMetadata->getAssociation('newAssociation1')
        );
    }

    public function testRenameAssociationViaProperty(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        $association1 = $entityMetadata->addAssociation(new AssociationMetadata('association1'));
        $association1->setDataType('string');

        $entityMetadata->renameProperty('association1', 'newAssociation1');
        self::assertFalse($entityMetadata->hasAssociation('association1'));
        self::assertFalse($entityMetadata->hasProperty('association1'));
        self::assertTrue($entityMetadata->hasAssociation('newAssociation1'));
        self::assertTrue($entityMetadata->hasProperty('newAssociation1'));
        $expectedNewAssociation1 = new AssociationMetadata('newAssociation1');
        $expectedNewAssociation1->setDataType('string');
        self::assertEquals(
            $expectedNewAssociation1,
            $entityMetadata->getAssociation('newAssociation1')
        );
    }

    public function testGetPropertyByPropertyPathWhenPropertyPathEqualsToName(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        $field1 = $entityMetadata->addField(new FieldMetadata('field1'));
        $association1 = $entityMetadata->addAssociation(new AssociationMetadata('association1'));
        $metaProperty1 = $entityMetadata->addMetaProperty(new MetaPropertyMetadata('metaProperty1'));

        self::assertSame($field1, $entityMetadata->getPropertyByPropertyPath('field1'));
        self::assertSame($association1, $entityMetadata->getPropertyByPropertyPath('association1'));
        self::assertSame($metaProperty1, $entityMetadata->getPropertyByPropertyPath('metaProperty1'));
        self::assertNull($entityMetadata->getPropertyByPropertyPath('unknown'));
    }

    public function testGetPropertyByPropertyPathWhenPropertyPathIsNotEqualToName(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        $field1 = $entityMetadata->addField(new FieldMetadata('field1'));
        $field1->setPropertyPath('realField1');
        $association1 = $entityMetadata->addAssociation(new AssociationMetadata('association1'));
        $association1->setPropertyPath('realAssociation1');
        $metaProperty1 = $entityMetadata->addMetaProperty(new MetaPropertyMetadata('metaProperty1'));
        $metaProperty1->setPropertyPath('realMetaProperty1');

        self::assertSame($field1, $entityMetadata->getPropertyByPropertyPath('realField1'));
        self::assertSame($association1, $entityMetadata->getPropertyByPropertyPath('realAssociation1'));
        self::assertSame($metaProperty1, $entityMetadata->getPropertyByPropertyPath('realMetaProperty1'));
        self::assertNull($entityMetadata->getPropertyByPropertyPath('unknown'));
    }

    public function testGetPropertyByPropertyPathForIgnoredPropertyPath(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        $field1 = $entityMetadata->addField(new FieldMetadata('field1'));
        $field1->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $association1 = $entityMetadata->addAssociation(new AssociationMetadata('association1'));
        $association1->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $metaProperty1 = $entityMetadata->addMetaProperty(new MetaPropertyMetadata('metaProperty1'));
        $metaProperty1->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);

        self::assertNull($entityMetadata->getPropertyByPropertyPath('field1'));
        self::assertNull($entityMetadata->getPropertyByPropertyPath('association1'));
        self::assertNull($entityMetadata->getPropertyByPropertyPath('metaProperty1'));
    }

    public function testAttributes(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        self::assertFalse($entityMetadata->has('attribute1'));
        self::assertNull($entityMetadata->get('attribute1'));

        $entityMetadata->remove('attribute1');
        self::assertFalse($entityMetadata->has('attribute1'));

        $entityMetadata->set('attribute1', 'value1');
        self::assertTrue($entityMetadata->has('attribute1'));
        self::assertEquals('value1', $entityMetadata->get('attribute1'));

        $entityMetadata->remove('attribute1');
        self::assertFalse($entityMetadata->has('attribute1'));
        self::assertNull($entityMetadata->get('attribute1'));
    }

    public function testLinks(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        self::assertCount(0, $entityMetadata->getLinks());
        self::assertFalse($entityMetadata->hasLink('unknown'));
        self::assertNull($entityMetadata->getLink('unknown'));

        $link1 = new ExternalLinkMetadata('url1');
        self::assertSame($link1, $entityMetadata->addLink('link1', $link1));
        $link2 = new ExternalLinkMetadata('url2');
        self::assertSame($link2, $entityMetadata->addLink('link2', $link2));
        self::assertCount(2, $entityMetadata->getLinks());

        self::assertTrue($entityMetadata->hasLink('link1'));
        self::assertSame($link1, $entityMetadata->getLink('link1'));

        $entityMetadata->removeLink('link1');
        self::assertCount(1, $entityMetadata->getLinks());
        self::assertFalse($entityMetadata->hasLink('link1'));
        self::assertTrue($entityMetadata->hasLink('link2'));

        $entityMetadata->removeLink('link2');
        self::assertCount(0, $entityMetadata->getLinks());
        self::assertFalse($entityMetadata->hasLink('link2'));
    }

    public function testHints(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        self::assertSame([], $entityMetadata->getHints());

        $entityMetadata->setHints(['HINT_TEST']);
        self::assertSame(['HINT_TEST'], $entityMetadata->getHints());
    }

    public function testHasIdFieldsOnlyForEmptyMetadata(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        self::assertFalse($entityMetadata->hasIdentifierFieldsOnly());
    }

    public function testHasIdFieldsOnlyWithoutFields(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        $entityMetadata->setIdentifierFieldNames(['id']);

        self::assertFalse($entityMetadata->hasIdentifierFieldsOnly());
    }

    public function testHasIdFieldsOnlyWithoutIdentifierFieldNames(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        $entityMetadata->addField(new FieldMetadata('id'));

        self::assertFalse($entityMetadata->hasIdentifierFieldsOnly());
    }

    public function testHasIdFieldsOnlyWhenMetadataContainsOnlySingleIdentityField(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        $entityMetadata->setIdentifierFieldNames(['id']);
        $entityMetadata->addField(new FieldMetadata('id'));

        self::assertTrue($entityMetadata->hasIdentifierFieldsOnly());
    }

    public function testHasIdFieldsOnlyWhenMetadataContainsSingleIdentityFieldAndMetaProperty(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        $entityMetadata->setIdentifierFieldNames(['id']);
        $entityMetadata->addField(new FieldMetadata('id'));
        $entityMetadata->addMetaProperty(new MetaPropertyMetadata('meta'));

        self::assertTrue($entityMetadata->hasIdentifierFieldsOnly());
    }

    public function testHasIdFieldsOnlyWhenMetadataContainsOnlyCompositeIdentityFields(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        $entityMetadata->setIdentifierFieldNames(['id1', 'id2']);
        $entityMetadata->addField(new FieldMetadata('id1'));
        $entityMetadata->addField(new FieldMetadata('id2'));

        self::assertTrue($entityMetadata->hasIdentifierFieldsOnly());
    }

    public function testHasIdFieldsOnlyWhenMetadataContainsOnlyCompositeIdentityFieldsReverseOrderOfFields(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        $entityMetadata->setIdentifierFieldNames(['id1', 'id2']);
        $entityMetadata->addField(new FieldMetadata('id2'));
        $entityMetadata->addField(new FieldMetadata('id1'));

        self::assertTrue($entityMetadata->hasIdentifierFieldsOnly());
    }

    public function testHasIdFieldsOnlyWhenMetadataContainsCompositeIdentityFieldsAndMetaProperty(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        $entityMetadata->setIdentifierFieldNames(['id1', 'id2']);
        $entityMetadata->addField(new FieldMetadata('id1'));
        $entityMetadata->addField(new FieldMetadata('id2'));
        $entityMetadata->addMetaProperty(new MetaPropertyMetadata('meta'));

        self::assertTrue($entityMetadata->hasIdentifierFieldsOnly());
    }

    public function testHasIdFieldsOnlyWhenMetadataContainsAdditionField(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        $entityMetadata->setIdentifierFieldNames(['id']);
        $entityMetadata->addField(new FieldMetadata('id'));
        $entityMetadata->addField(new FieldMetadata('field1'));

        self::assertFalse($entityMetadata->hasIdentifierFieldsOnly());
    }

    public function testHasIdFieldsOnlyWhenMetadataContainsAssociation(): void
    {
        $entityMetadata = new EntityMetadata('Test\Class');
        $entityMetadata->setIdentifierFieldNames(['id']);
        $entityMetadata->addField(new FieldMetadata('id'));
        $entityMetadata->addAssociation(new AssociationMetadata('association1'));

        self::assertFalse($entityMetadata->hasIdentifierFieldsOnly());
    }

    public function testGetIdentifierValueForEntityWithoutId(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The entity "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group" does not have identifier field(s).'
        );

        $entity = new Group();

        $entityMetadata = new EntityMetadata(Group::class);

        $entityMetadata->getIdentifierValue($entity);
    }

    public function testGetIdentifierValueForEntityWithSingleId(): void
    {
        $entity = new Group();
        $entity->setId(123);

        $entityMetadata = new EntityMetadata(Group::class);
        $entityMetadata->setIdentifierFieldNames(['id']);

        self::assertSame(
            123,
            $entityMetadata->getIdentifierValue($entity)
        );
    }

    public function testGetIdentifierValueForEntityWithRenamedSingleId(): void
    {
        $entity = new Group();
        $entity->setId(123);

        $entityMetadata = new EntityMetadata(Group::class);
        $entityMetadata->setIdentifierFieldNames(['renamedId']);
        $entityMetadata->addField(new FieldMetadata('renamedId'))->setPropertyPath('id');

        self::assertSame(
            123,
            $entityMetadata->getIdentifierValue($entity)
        );
    }

    public function testGetIdentifierValueForEntityWithSingleIdWhenIdPropertyDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'The class "%s" does not have property "unknownProperty".',
            Group::class
        ));

        $entity = new Group();
        $entity->setId(123);

        $entityMetadata = new EntityMetadata(Group::class);
        $entityMetadata->setIdentifierFieldNames(['unknownProperty']);

        $entityMetadata->getIdentifierValue($entity);
    }

    public function testGetIdentifierValueForEntityWithCompositeId(): void
    {
        $entity = new Group();
        $entity->setId(123);
        $entity->setName('test');

        $entityMetadata = new EntityMetadata(Group::class);
        $entityMetadata->setIdentifierFieldNames(['id', 'name']);

        self::assertSame(
            ['id' => 123, 'name' => 'test'],
            $entityMetadata->getIdentifierValue($entity)
        );
    }

    public function testGetIdentifierValueForEntityWithRenamedCompositeId(): void
    {
        $entity = new Group();
        $entity->setId(123);
        $entity->setName('test');

        $entityMetadata = new EntityMetadata(Group::class);
        $entityMetadata->setIdentifierFieldNames(['renamedId', 'renamedName']);
        $entityMetadata->addField(new FieldMetadata('renamedId'))->setPropertyPath('id');
        $entityMetadata->addField(new FieldMetadata('renamedName'))->setPropertyPath('name');

        self::assertSame(
            ['renamedId' => 123, 'renamedName' => 'test'],
            $entityMetadata->getIdentifierValue($entity)
        );
    }

    public function testGetIdentifierValueForEntityWithRenamedCompositeIdWhenIdPropertyDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'The class "%s" does not have property "unknownProperty".',
            Group::class
        ));

        $entity = new Group();
        $entity->setId(123);
        $entity->setName('test');

        $entityMetadata = new EntityMetadata(Group::class);
        $entityMetadata->setIdentifierFieldNames(['id', 'unknownProperty']);

        $entityMetadata->getIdentifierValue($entity);
    }
}
