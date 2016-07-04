<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;

class EntityMetadataTest extends \PHPUnit_Framework_TestCase
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

        $entityMetadataClone = clone $entityMetadata;

        $this->assertEquals($entityMetadata, $entityMetadataClone);
        $this->assertNotSame($objValue, $entityMetadataClone->get('test_object'));
        $this->assertNotSame($metaProperty1, $entityMetadataClone->getMetaProperty('metaProperty1'));
        $this->assertNotSame($field1, $entityMetadataClone->getField('field1'));
        $this->assertNotSame($association1, $entityMetadataClone->getAssociation('association1'));
    }

    public function testCloneForEmptyEntityMetadata()
    {
        $entityMetadata = new EntityMetadata();

        $entityMetadataClone = clone $entityMetadata;

        $this->assertEquals($entityMetadata, $entityMetadataClone);
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

        $this->assertEquals(
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
                'fields'                   => [
                    'field1' => [
                        'data_type' => 'testDataType'
                    ]
                ],
                'associations'             => [
                    'association1' => [
                        'data_type' => 'testDataType'
                    ]
                ],
            ],
            $entityMetadata->toArray()
        );
    }

    public function testToArrayForEmptyEntityMetadata()
    {
        $associationMetadata = new EntityMetadata();

        $this->assertEquals(
            [],
            $associationMetadata->toArray()
        );
    }

    public function testClassName()
    {
        $entityMetadata = new EntityMetadata();
        $this->assertNull($entityMetadata->getClassName());

        $entityMetadata->setClassName('Test\Class');
        $this->assertEquals('Test\Class', $entityMetadata->getClassName());
    }

    public function testIdentifierFieldNames()
    {
        $entityMetadata = new EntityMetadata();
        $this->assertEmpty($entityMetadata->getIdentifierFieldNames());

        $entityMetadata->setIdentifierFieldNames(['id']);
        $this->assertEquals(['id'], $entityMetadata->getIdentifierFieldNames());
    }

    public function testInheritedType()
    {
        $entityMetadata = new EntityMetadata();
        $this->assertFalse($entityMetadata->isInheritedType());

        $entityMetadata->setInheritedType(true);
        $this->assertTrue($entityMetadata->isInheritedType());

        $entityMetadata->setInheritedType(false);
        $this->assertFalse($entityMetadata->isInheritedType());
    }

    public function testIdentifierGenerator()
    {
        $entityMetadata = new EntityMetadata();
        $this->assertFalse($entityMetadata->hasIdentifierGenerator());

        $entityMetadata->setHasIdentifierGenerator(true);
        $this->assertTrue($entityMetadata->hasIdentifierGenerator());

        $entityMetadata->setHasIdentifierGenerator(false);
        $this->assertFalse($entityMetadata->hasIdentifierGenerator());
    }

    public function testMetaProperties()
    {
        $entityMetadata = new EntityMetadata();
        $this->assertCount(0, $entityMetadata->getMetaProperties());
        $this->assertFalse($entityMetadata->hasProperty('unknown'));
        $this->assertFalse($entityMetadata->hasMetaProperty('unknown'));
        $this->assertNull($entityMetadata->getMetaProperty('unknown'));

        $property1 = new MetaPropertyMetadata('property1');
        $this->assertSame($property1, $entityMetadata->addMetaProperty($property1));
        $property2 = new MetaPropertyMetadata('property2');
        $this->assertSame($property2, $entityMetadata->addMetaProperty($property2));
        $this->assertCount(2, $entityMetadata->getMetaProperties());

        $this->assertTrue($entityMetadata->hasProperty('property1'));
        $this->assertTrue($entityMetadata->hasMetaProperty('property1'));
        $this->assertSame($property1, $entityMetadata->getMetaProperty('property1'));

        $entityMetadata->removeMetaProperty('property1');
        $this->assertCount(1, $entityMetadata->getMetaProperties());
        $this->assertFalse($entityMetadata->hasProperty('property1'));
        $this->assertFalse($entityMetadata->hasMetaProperty('property1'));

        $entityMetadata->removeProperty('property2');
        $this->assertCount(0, $entityMetadata->getMetaProperties());
        $this->assertFalse($entityMetadata->hasProperty('property2'));
        $this->assertFalse($entityMetadata->hasMetaProperty('property2'));
    }

    public function testFields()
    {
        $entityMetadata = new EntityMetadata();
        $this->assertCount(0, $entityMetadata->getFields());
        $this->assertFalse($entityMetadata->hasProperty('unknown'));
        $this->assertFalse($entityMetadata->hasField('unknown'));
        $this->assertNull($entityMetadata->getField('unknown'));

        $field1 = new FieldMetadata('field1');
        $this->assertSame($field1, $entityMetadata->addField($field1));
        $field2 = new FieldMetadata('field2');
        $this->assertSame($field2, $entityMetadata->addField($field2));
        $this->assertCount(2, $entityMetadata->getFields());

        $this->assertTrue($entityMetadata->hasProperty('field1'));
        $this->assertTrue($entityMetadata->hasField('field1'));
        $this->assertSame($field1, $entityMetadata->getField('field1'));

        $entityMetadata->removeField('field1');
        $this->assertCount(1, $entityMetadata->getFields());
        $this->assertFalse($entityMetadata->hasProperty('field1'));
        $this->assertFalse($entityMetadata->hasField('field1'));

        $entityMetadata->removeProperty('field2');
        $this->assertCount(0, $entityMetadata->getFields());
        $this->assertFalse($entityMetadata->hasProperty('field2'));
        $this->assertFalse($entityMetadata->hasField('field2'));
    }

    public function testAssociations()
    {
        $entityMetadata = new EntityMetadata();
        $this->assertCount(0, $entityMetadata->getAssociations());
        $this->assertFalse($entityMetadata->hasProperty('unknown'));
        $this->assertFalse($entityMetadata->hasAssociation('unknown'));
        $this->assertNull($entityMetadata->getAssociation('unknown'));

        $association1 = new AssociationMetadata('association1');
        $this->assertSame($association1, $entityMetadata->addAssociation($association1));
        $association2 = new AssociationMetadata('association2');
        $this->assertSame($association2, $entityMetadata->addAssociation($association2));
        $this->assertCount(2, $entityMetadata->getAssociations());

        $this->assertTrue($entityMetadata->hasProperty('association1'));
        $this->assertTrue($entityMetadata->hasAssociation('association1'));
        $this->assertSame($association1, $entityMetadata->getAssociation('association1'));

        $entityMetadata->removeAssociation('association1');
        $this->assertCount(1, $entityMetadata->getAssociations());
        $this->assertFalse($entityMetadata->hasProperty('association1'));
        $this->assertFalse($entityMetadata->hasAssociation('association1'));

        $entityMetadata->removeProperty('association2');
        $this->assertCount(0, $entityMetadata->getAssociations());
        $this->assertFalse($entityMetadata->hasProperty('association2'));
        $this->assertFalse($entityMetadata->hasAssociation('association2'));
    }

    public function testRenameMetaProperty()
    {
        $entityMetadata = new EntityMetadata();
        $property1 = $entityMetadata->addMetaProperty(new MetaPropertyMetadata('property1'));
        $property1->setDataType('string');

        $entityMetadata->renameMetaProperty('property1', 'newProperty1');
        $this->assertFalse($entityMetadata->hasMetaProperty('property1'));
        $this->assertFalse($entityMetadata->hasProperty('property1'));
        $this->assertTrue($entityMetadata->hasMetaProperty('newProperty1'));
        $this->assertTrue($entityMetadata->hasProperty('newProperty1'));
        $expectedNewProperty1 = new MetaPropertyMetadata('newProperty1');
        $expectedNewProperty1->setDataType('string');
        $this->assertEquals(
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
        $this->assertFalse($entityMetadata->hasMetaProperty('property1'));
        $this->assertFalse($entityMetadata->hasProperty('property1'));
        $this->assertTrue($entityMetadata->hasMetaProperty('newProperty1'));
        $this->assertTrue($entityMetadata->hasProperty('newProperty1'));
        $expectedNewProperty1 = new MetaPropertyMetadata('newProperty1');
        $expectedNewProperty1->setDataType('string');
        $this->assertEquals(
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
        $this->assertFalse($entityMetadata->hasField('field1'));
        $this->assertFalse($entityMetadata->hasProperty('field1'));
        $this->assertTrue($entityMetadata->hasField('newField1'));
        $this->assertTrue($entityMetadata->hasProperty('newField1'));
        $expectedNewField1 = new FieldMetadata('newField1');
        $expectedNewField1->setDataType('string');
        $this->assertEquals(
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
        $this->assertFalse($entityMetadata->hasField('field1'));
        $this->assertFalse($entityMetadata->hasProperty('field1'));
        $this->assertTrue($entityMetadata->hasField('newField1'));
        $this->assertTrue($entityMetadata->hasProperty('newField1'));
        $expectedNewField1 = new FieldMetadata('newField1');
        $expectedNewField1->setDataType('string');
        $this->assertEquals(
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
        $this->assertFalse($entityMetadata->hasAssociation('association1'));
        $this->assertFalse($entityMetadata->hasProperty('association1'));
        $this->assertTrue($entityMetadata->hasAssociation('newAssociation1'));
        $this->assertTrue($entityMetadata->hasProperty('newAssociation1'));
        $expectedNewAssociation1 = new AssociationMetadata('newAssociation1');
        $expectedNewAssociation1->setDataType('string');
        $this->assertEquals(
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
        $this->assertFalse($entityMetadata->hasAssociation('association1'));
        $this->assertFalse($entityMetadata->hasProperty('association1'));
        $this->assertTrue($entityMetadata->hasAssociation('newAssociation1'));
        $this->assertTrue($entityMetadata->hasProperty('newAssociation1'));
        $expectedNewAssociation1 = new AssociationMetadata('newAssociation1');
        $expectedNewAssociation1->setDataType('string');
        $this->assertEquals(
            $expectedNewAssociation1,
            $entityMetadata->getAssociation('newAssociation1')
        );
    }

    public function testAttributes()
    {
        $entityMetadata = new EntityMetadata();
        $this->assertFalse($entityMetadata->has('attribute1'));
        $this->assertNull($entityMetadata->get('attribute1'));

        $entityMetadata->remove('attribute1');
        $this->assertFalse($entityMetadata->has('attribute1'));

        $entityMetadata->set('attribute1', 'value1');
        $this->assertTrue($entityMetadata->has('attribute1'));
        $this->assertEquals('value1', $entityMetadata->get('attribute1'));

        $entityMetadata->remove('attribute1');
        $this->assertFalse($entityMetadata->has('attribute1'));
        $this->assertNull($entityMetadata->get('attribute1'));
    }
}
