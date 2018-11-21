<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\ExternalLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class EntityMetadataTest extends \PHPUnit\Framework\TestCase
{
    public function testClone()
    {
        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName('Test\Class');
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

        $entityMetadataClone = clone $entityMetadata;

        self::assertEquals($entityMetadata, $entityMetadataClone);
        self::assertNotSame($objValue, $entityMetadataClone->get('test_object'));
        self::assertNotSame($metaProperty1, $entityMetadataClone->getMetaProperty('metaProperty1'));
        self::assertNotSame($field1, $entityMetadataClone->getField('field1'));
        self::assertNotSame($association1, $entityMetadataClone->getAssociation('association1'));
    }

    public function testCloneForEmptyEntityMetadata()
    {
        $entityMetadata = new EntityMetadata();

        $entityMetadataClone = clone $entityMetadata;

        self::assertEquals($entityMetadata, $entityMetadataClone);
    }

    public function testToArray()
    {
        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName('Test\Class');
        $entityMetadata->setInheritedType(true);
        $entityMetadata->setHasIdentifierGenerator(true);
        $entityMetadata->setIdentifierFieldNames(['field1']);
        $entityMetadata->set('test_scalar', 'value');
        $objValue = new \stdClass();
        $objValue->someProp = 123;
        $entityMetadata->set('test_object', $objValue);
        $metaProperty1 = new MetaPropertyMetadata('metaProperty1');
        $metaProperty1->setDataType('testDataType');
        $entityMetadata->addMetaProperty($metaProperty1);
        $field1 = new FieldMetadata('field1');
        $field1->setDataType('testDataType');
        $entityMetadata->addField($field1);
        $association1 = new AssociationMetadata('association1');
        $association1->setDataType('testDataType');
        $entityMetadata->addAssociation($association1);
        $entityMetadata->addLink('link1', new ExternalLinkMetadata('url1'));

        self::assertEquals(
            [
                'class'                    => 'Test\Class',
                'inherited'                => true,
                'has_identifier_generator' => true,
                'identifiers'              => ['field1'],
                'test_scalar'              => 'value',
                'test_object'              => $objValue,
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

    public function testToArrayForEmptyEntityMetadata()
    {
        $associationMetadata = new EntityMetadata();

        self::assertEquals(
            [],
            $associationMetadata->toArray()
        );
    }

    public function testClassName()
    {
        $entityMetadata = new EntityMetadata();
        self::assertNull($entityMetadata->getClassName());

        $entityMetadata->setClassName('Test\Class');
        self::assertEquals('Test\Class', $entityMetadata->getClassName());
    }

    public function testIdentifierFieldNames()
    {
        $entityMetadata = new EntityMetadata();
        self::assertEmpty($entityMetadata->getIdentifierFieldNames());
        self::assertFalse($entityMetadata->hasIdentifierFields());

        $entityMetadata->setIdentifierFieldNames(['id']);
        self::assertEquals(['id'], $entityMetadata->getIdentifierFieldNames());
        self::assertTrue($entityMetadata->hasIdentifierFields());
    }

    public function testInheritedType()
    {
        $entityMetadata = new EntityMetadata();
        self::assertFalse($entityMetadata->isInheritedType());

        $entityMetadata->setInheritedType(true);
        self::assertTrue($entityMetadata->isInheritedType());

        $entityMetadata->setInheritedType(false);
        self::assertFalse($entityMetadata->isInheritedType());
    }

    public function testIdentifierGenerator()
    {
        $entityMetadata = new EntityMetadata();
        self::assertFalse($entityMetadata->hasIdentifierGenerator());

        $entityMetadata->setHasIdentifierGenerator(true);
        self::assertTrue($entityMetadata->hasIdentifierGenerator());

        $entityMetadata->setHasIdentifierGenerator(false);
        self::assertFalse($entityMetadata->hasIdentifierGenerator());
    }

    public function testMetaProperties()
    {
        $entityMetadata = new EntityMetadata();
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

    public function testFields()
    {
        $entityMetadata = new EntityMetadata();
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

    public function testAssociations()
    {
        $entityMetadata = new EntityMetadata();
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

    public function testRenameMetaProperty()
    {
        $entityMetadata = new EntityMetadata();
        $property1 = $entityMetadata->addMetaProperty(new MetaPropertyMetadata('property1'));
        $property1->setDataType('string');

        $entityMetadata->renameMetaProperty('property1', 'newProperty1');
        self::assertFalse($entityMetadata->hasMetaProperty('property1'));
        self::assertFalse($entityMetadata->hasProperty('property1'));
        self::assertTrue($entityMetadata->hasMetaProperty('newProperty1'));
        self::assertTrue($entityMetadata->hasProperty('newProperty1'));
        $expectedNewProperty1 = new MetaPropertyMetadata('newProperty1');
        $expectedNewProperty1->setDataType('string');
        self::assertEquals(
            $expectedNewProperty1,
            $entityMetadata->getMetaProperty('newProperty1')
        );
    }

    public function testRenameMetaPropertyViaProperty()
    {
        $entityMetadata = new EntityMetadata();
        $property1 = $entityMetadata->addMetaProperty(new MetaPropertyMetadata('property1'));
        $property1->setDataType('string');

        $entityMetadata->renameProperty('property1', 'newProperty1');
        self::assertFalse($entityMetadata->hasMetaProperty('property1'));
        self::assertFalse($entityMetadata->hasProperty('property1'));
        self::assertTrue($entityMetadata->hasMetaProperty('newProperty1'));
        self::assertTrue($entityMetadata->hasProperty('newProperty1'));
        $expectedNewProperty1 = new MetaPropertyMetadata('newProperty1');
        $expectedNewProperty1->setDataType('string');
        self::assertEquals(
            $expectedNewProperty1,
            $entityMetadata->getMetaProperty('newProperty1')
        );
    }

    public function testRenameField()
    {
        $entityMetadata = new EntityMetadata();
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

    public function testRenameFieldViaProperty()
    {
        $entityMetadata = new EntityMetadata();
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

    public function testRenameAssociation()
    {
        $entityMetadata = new EntityMetadata();
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

    public function testRenameAssociationViaProperty()
    {
        $entityMetadata = new EntityMetadata();
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

    public function testGetPropertyByPropertyPathWhenPropertyPathEqualsToName()
    {
        $entityMetadata = new EntityMetadata();
        $field1 = $entityMetadata->addField(new FieldMetadata('field1'));
        $association1 = $entityMetadata->addAssociation(new AssociationMetadata('association1'));
        $metaProperty1 = $entityMetadata->addMetaProperty(new MetaPropertyMetadata('metaProperty1'));

        self::assertSame($field1, $entityMetadata->getPropertyByPropertyPath('field1'));
        self::assertSame($association1, $entityMetadata->getPropertyByPropertyPath('association1'));
        self::assertSame($metaProperty1, $entityMetadata->getPropertyByPropertyPath('metaProperty1'));
        self::assertNull($entityMetadata->getPropertyByPropertyPath('unknown'));
    }

    public function testGetPropertyByPropertyPathWhenPropertyPathIsNotEqualToName()
    {
        $entityMetadata = new EntityMetadata();
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

    public function testGetPropertyByPropertyPathForIgnoredPropertyPath()
    {
        $entityMetadata = new EntityMetadata();
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

    public function testAttributes()
    {
        $entityMetadata = new EntityMetadata();
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

    public function testLinks()
    {
        $entityMetadata = new EntityMetadata();
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

    public function testHasIdentifierFieldsOnlyForEmptyMetadata()
    {
        $entityMetadata = new EntityMetadata();
        self::assertFalse($entityMetadata->hasIdentifierFieldsOnly());
    }

    public function testHasIdentifierFieldsOnlyWithoutFields()
    {
        $entityMetadata = new EntityMetadata();
        $entityMetadata->setIdentifierFieldNames(['id']);

        self::assertFalse($entityMetadata->hasIdentifierFieldsOnly());
    }

    public function testHasIdentifierFieldsOnlyWithoutIdentifierFieldNames()
    {
        $entityMetadata = new EntityMetadata();
        $entityMetadata->addField(new FieldMetadata('id'));

        self::assertFalse($entityMetadata->hasIdentifierFieldsOnly());
    }

    public function testHasIdentifierFieldsOnlyWhenMetadataContainsOnlySingleIdentityField()
    {
        $entityMetadata = new EntityMetadata();
        $entityMetadata->setIdentifierFieldNames(['id']);
        $entityMetadata->addField(new FieldMetadata('id'));

        self::assertTrue($entityMetadata->hasIdentifierFieldsOnly());
    }

    public function testHasIdentifierFieldsOnlyWhenMetadataContainsSingleIdentityFieldAndMetaProperty()
    {
        $entityMetadata = new EntityMetadata();
        $entityMetadata->setIdentifierFieldNames(['id']);
        $entityMetadata->addField(new FieldMetadata('id'));
        $entityMetadata->addMetaProperty(new MetaPropertyMetadata('meta'));

        self::assertTrue($entityMetadata->hasIdentifierFieldsOnly());
    }

    public function testHasIdentifierFieldsOnlyWhenMetadataContainsOnlyCompositeIdentityFields()
    {
        $entityMetadata = new EntityMetadata();
        $entityMetadata->setIdentifierFieldNames(['id1', 'id2']);
        $entityMetadata->addField(new FieldMetadata('id1'));
        $entityMetadata->addField(new FieldMetadata('id2'));

        self::assertTrue($entityMetadata->hasIdentifierFieldsOnly());
    }

    public function testHasIdentifierFieldsOnlyWhenMetadataContainsOnlyCompositeIdentityFieldsReverseOrderOfFields()
    {
        $entityMetadata = new EntityMetadata();
        $entityMetadata->setIdentifierFieldNames(['id1', 'id2']);
        $entityMetadata->addField(new FieldMetadata('id2'));
        $entityMetadata->addField(new FieldMetadata('id1'));

        self::assertTrue($entityMetadata->hasIdentifierFieldsOnly());
    }

    public function testHasIdentifierFieldsOnlyWhenMetadataContainsCompositeIdentityFieldsAndMetaProperty()
    {
        $entityMetadata = new EntityMetadata();
        $entityMetadata->setIdentifierFieldNames(['id1', 'id2']);
        $entityMetadata->addField(new FieldMetadata('id1'));
        $entityMetadata->addField(new FieldMetadata('id2'));
        $entityMetadata->addMetaProperty(new MetaPropertyMetadata('meta'));

        self::assertTrue($entityMetadata->hasIdentifierFieldsOnly());
    }

    public function testHasIdentifierFieldsOnlyWhenMetadataContainsAdditionField()
    {
        $entityMetadata = new EntityMetadata();
        $entityMetadata->setIdentifierFieldNames(['id']);
        $entityMetadata->addField(new FieldMetadata('id'));
        $entityMetadata->addField(new FieldMetadata('field1'));

        self::assertFalse($entityMetadata->hasIdentifierFieldsOnly());
    }

    public function testHasIdentifierFieldsOnlyWhenMetadataContainsAssociation()
    {
        $entityMetadata = new EntityMetadata();
        $entityMetadata->setIdentifierFieldNames(['id']);
        $entityMetadata->addField(new FieldMetadata('id'));
        $entityMetadata->addAssociation(new AssociationMetadata('association1'));

        self::assertFalse($entityMetadata->hasIdentifierFieldsOnly());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected argument of type "object", "integer" given.
     */
    public function testGetIdentifierValueForInvalidInputEntity()
    {
        $entityMetadata = new EntityMetadata();

        $entityMetadata->getIdentifierValue(123);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The entity "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group" does not have identifier field(s).
     */
    // @codingStandardsIgnoreEnd
    public function testGetIdentifierValueForEntityWithoutId()
    {
        $entity = new Group();

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName(Group::class);

        $entityMetadata->getIdentifierValue($entity);
    }

    public function testGetIdentifierValueForEntityWithSingleId()
    {
        $entity = new Group();
        $entity->setId(123);

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName(Group::class);
        $entityMetadata->setIdentifierFieldNames(['id']);

        self::assertSame(
            123,
            $entityMetadata->getIdentifierValue($entity)
        );
    }

    public function testGetIdentifierValueForEntityWithRenamedSingleId()
    {
        $entity = new Group();
        $entity->setId(123);

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName(Group::class);
        $entityMetadata->setIdentifierFieldNames(['renamedId']);
        $entityMetadata->addField(new FieldMetadata('renamedId'))->setPropertyPath('id');

        self::assertSame(
            123,
            $entityMetadata->getIdentifierValue($entity)
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The class "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group" does not have property "unknownProperty".
     */
    // @codingStandardsIgnoreEnd
    public function testGetIdentifierValueForEntityWithSingleIdWhenIdPropertyDoesNotExist()
    {
        $entity = new Group();
        $entity->setId(123);

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName(Group::class);
        $entityMetadata->setIdentifierFieldNames(['unknownProperty']);

        $entityMetadata->getIdentifierValue($entity);
    }

    public function testGetIdentifierValueForEntityWithCompositeId()
    {
        $entity = new Group();
        $entity->setId(123);
        $entity->setName('test');

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName(Group::class);
        $entityMetadata->setIdentifierFieldNames(['id', 'name']);

        self::assertSame(
            ['id' => 123, 'name' => 'test'],
            $entityMetadata->getIdentifierValue($entity)
        );
    }

    public function testGetIdentifierValueForEntityWithRenamedCompositeId()
    {
        $entity = new Group();
        $entity->setId(123);
        $entity->setName('test');

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName(Group::class);
        $entityMetadata->setIdentifierFieldNames(['renamedId', 'renamedName']);
        $entityMetadata->addField(new FieldMetadata('renamedId'))->setPropertyPath('id');
        $entityMetadata->addField(new FieldMetadata('renamedName'))->setPropertyPath('name');

        self::assertSame(
            ['renamedId' => 123, 'renamedName' => 'test'],
            $entityMetadata->getIdentifierValue($entity)
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The class "Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group" does not have property "unknownProperty".
     */
    // @codingStandardsIgnoreEnd
    public function testGetIdentifierValueForEntityWithRenamedCompositeIdWhenIdPropertyDoesNotExist()
    {
        $entity = new Group();
        $entity->setId(123);
        $entity->setName('test');

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName(Group::class);
        $entityMetadata->setIdentifierFieldNames(['id', 'unknownProperty']);

        $entityMetadata->getIdentifierValue($entity);
    }
}
