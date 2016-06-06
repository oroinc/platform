<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;

class EntityMetadataTest extends OrmRelatedTestCase
{
    /** @var ClassMetadata */
    protected $classMetadata;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->classMetadata = $this->em->getClassMetadata('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User');
    }

    public function testGetSetClassName()
    {
        $entityMetadata = new EntityMetadata();

        $this->assertNull($entityMetadata->getClassName());

        $entityMetadata->setClassName($this->classMetadata->getName());
        $this->assertSame($this->classMetadata->getName(), $entityMetadata->getClassName());

        $this->assertSame(
            [
                'class'       => $this->classMetadata->getName(),
                'identifiers' => []
            ],
            $entityMetadata->toArray()
        );
    }

    public function testGetSetIdentifierFieldNames()
    {
        $entityMetadata = new EntityMetadata();

        $this->assertEmpty($entityMetadata->getIdentifierFieldNames());

        $entityMetadata->setIdentifierFieldNames($this->classMetadata->getIdentifierFieldNames());
        $this->assertNotEmpty($entityMetadata->getIdentifierFieldNames());
        $this->assertSame($this->classMetadata->getIdentifierFieldNames(), $entityMetadata->getIdentifierFieldNames());

        $this->assertSame(
            [
                'identifiers' => ['id']
            ],
            $entityMetadata->toArray()
        );
    }

    public function testInheritedType()
    {
        $entityMetadata = new EntityMetadata();

        $this->assertFalse($entityMetadata->isInheritedType());

        $entityMetadata->setInheritedType(true);
        $this->assertTrue($entityMetadata->isInheritedType());

        $entityMetadata->setInheritedType(ClassMetadata::INHERITANCE_TYPE_NONE);
        $this->assertTrue($entityMetadata->isInheritedType());
        $this->assertSame(ClassMetadata::INHERITANCE_TYPE_NONE, $entityMetadata->get(EntityMetadata::INHERITED));

        $this->assertSame(
            [
                EntityMetadata::INHERITED => 1,
                'identifiers'             => []
            ],
            $entityMetadata->toArray()
        );
    }

    public function testFields()
    {
        $entityMetadata = new EntityMetadata();

        $this->assertEmpty($entityMetadata->getFields());

        /**
         * FieldNames: id, name
         */
        foreach ($this->classMetadata->getFieldNames() as $index => $fieldName) {
            $this->assertCount($index, $entityMetadata->getFields());

            $this->assertFalse($entityMetadata->hasField($fieldName));
            $this->assertNull($entityMetadata->getField($fieldName));

            $entityMetadata->addField($this->getMetadata('Field', $fieldName));

            $this->assertCount(++$index, $entityMetadata->getFields());
        }

        $this->assertCount(count($this->classMetadata->getFieldNames()), $entityMetadata->getFields());

        $entityMetadata->removeField('id');

        $this->assertCount(1, $entityMetadata->getFields());
        $this->assertFalse($entityMetadata->hasProperty('id'));
        $this->assertFalse($entityMetadata->hasField('id'));

        $this->assertSame(
            [
                'identifiers' => [],
                'fields'      => [
                    'name' => [
                        'dataType' => 'string'
                    ]
                ]
            ],
            $entityMetadata->toArray()
        );

        $entityMetadata->removeProperty('name');

        $this->assertFalse($entityMetadata->hasProperty('name'));
        $this->assertFalse($entityMetadata->hasField('name'));

        $this->assertEmpty($entityMetadata->getFields());
        $this->assertEmpty($entityMetadata->getAssociations());
    }

    public function testAssociations()
    {
        $entityMetadata = new EntityMetadata();

        $this->assertEmpty($entityMetadata->getAssociations());

        /**
         * AssociationNames: category, groups, product, owner
         */
        foreach ($this->classMetadata->getAssociationNames() as $index => $associationName) {
            $this->assertCount($index, $entityMetadata->getAssociations());
            $this->assertFalse($entityMetadata->hasAssociation($associationName));
            $this->assertNull($entityMetadata->getAssociation($associationName));

            $entityMetadata->addAssociation($this->getMetadata('Association', $associationName));

            $this->assertCount(++$index, $entityMetadata->getAssociations());
        }

        $this->assertCount(count($this->classMetadata->getAssociationNames()), $entityMetadata->getAssociations());

        $entityMetadata->removeAssociation('category');

        $this->assertCount(3, $entityMetadata->getAssociations());
        $this->assertFalse($entityMetadata->hasAssociation('category'));
        $this->assertSame(
            [
                'identifiers'  => [],
                'associations' => [
                    'groups'   => [],
                    'products' => [],
                    'owner'    => [],
                ]
            ],
            $entityMetadata->toArray()
        );

        $entityMetadata->removeProperty('products');

        $this->assertFalse($entityMetadata->hasProperty('products'));
        $this->assertFalse($entityMetadata->hasAssociation('products'));

        $this->assertCount(2, $entityMetadata->getAssociations());
        $this->assertEmpty($entityMetadata->getFields());
    }

    public function testRenameFieldAssociation()
    {
        $entityMetadata = new EntityMetadata();

        foreach ($this->classMetadata->getFieldNames() as $index => $fieldName) {
            $entityMetadata->addField($this->getMetadata('Field', $fieldName));
        }

        /**
         * Rename Field
         */
        $this->assertTrue($entityMetadata->hasField('name'));

        $entityMetadata->renameField('name', 'newName');

        $this->assertFalse($entityMetadata->hasField('name'));
        $this->assertFalse($entityMetadata->hasProperty('name'));
        $this->assertTrue($entityMetadata->hasField('newName'));
        $this->assertTrue($entityMetadata->hasProperty('newName'));
        $this->assertEquals(
            $this->getMetadata('Field', 'name', 'newName'),
            $entityMetadata->getField('newName')
        );

        /**
         * Rename Field via property
         */
        $this->assertTrue($entityMetadata->hasProperty('id'));

        $entityMetadata->renameProperty('id', 'newId');

        $this->assertFalse($entityMetadata->hasField('id'));
        $this->assertFalse($entityMetadata->hasProperty('id'));
        $this->assertTrue($entityMetadata->hasField('newId'));
        $this->assertTrue($entityMetadata->hasProperty('newId'));
        $this->assertEquals(
            $this->getMetadata('Field', 'id', 'newId'),
            $entityMetadata->getField('newId')
        );

        foreach ($this->classMetadata->getAssociationNames() as $index => $associationName) {
            $entityMetadata->addAssociation($this->getMetadata('Association', $associationName));
        }

        /**
         * Rename Association
         */
        $this->assertTrue($entityMetadata->hasAssociation('groups'));

        $entityMetadata->renameAssociation('groups', 'newGroups');

        $this->assertFalse($entityMetadata->hasAssociation('groups'));
        $this->assertFalse($entityMetadata->hasProperty('groups'));
        $this->assertTrue($entityMetadata->hasAssociation('newGroups'));
        $this->assertTrue($entityMetadata->hasProperty('newGroups'));
        $this->assertEquals(
            $this->getMetadata('Association', 'groups', 'newGroups'),
            $entityMetadata->getAssociation('newGroups')
        );

        /**
         * Rename Association via property
         */
        $this->assertTrue($entityMetadata->hasProperty('products'));

        $entityMetadata->renameProperty('products', 'newProducts');

        $this->assertFalse($entityMetadata->hasAssociation('products'));
        $this->assertFalse($entityMetadata->hasProperty('products'));
        $this->assertTrue($entityMetadata->hasAssociation('newProducts'));
        $this->assertTrue($entityMetadata->hasProperty('newProducts'));
        $this->assertEquals(
            $this->getMetadata('Association', 'products', 'newProducts'),
            $entityMetadata->getAssociation('newProducts')
        );
    }

    /**
     * @param string $type 'Field' or 'Association'
     * @param string $fieldName
     * @param string $customFieldName
     *
     * @return FieldMetadata|AssociationMetadata
     */
    protected function getMetadata($type, $fieldName, $customFieldName = null)
    {
        $typeClass = 'Oro\\Bundle\\ApiBundle\\Metadata\\' . $type . 'Metadata';

        $metadata = new $typeClass();

        $metadata->setName($customFieldName ? : $fieldName);
        if ($type === 'Field') {
            $metadata->setDataType($this->classMetadata->getTypeOfField($fieldName));
        }

        return $metadata;
    }
}
