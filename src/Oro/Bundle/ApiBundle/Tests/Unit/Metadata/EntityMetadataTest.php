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
        $entityMetadata->renameField('name', 'newName');

        $this->assertCount(1, $entityMetadata->getFields());
        $this->assertEquals($this->getMetadata('Field', 'name', 'newName'), $entityMetadata->getField('newName'));

        $this->assertFalse($entityMetadata->hasProperty('id'));
        $this->assertTrue($entityMetadata->hasProperty('newName'));

        $this->assertSame(
            [
                'identifiers' => [],
                'fields'      => [
                    'newName' => [
                        'dataType' => 'string'
                    ]
                ]
            ],
            $entityMetadata->toArray()
        );

        $entityMetadata->renameProperty('newName', 'name');

        $this->assertFalse($entityMetadata->hasProperty('newName'));
        $this->assertFalse($entityMetadata->hasField('newName'));
        $this->assertTrue($entityMetadata->hasProperty('name'));
        $this->assertTrue($entityMetadata->hasField('name'));

        $entityMetadata->removeProperty('name');

        $this->assertFalse($entityMetadata->hasProperty('name'));
        $this->assertFalse($entityMetadata->hasField('name'));

        $this->assertEmpty($entityMetadata->getFields());
    }

    public function testAssociations()
    {
        $entityMetadata = new EntityMetadata();

        $this->assertEmpty($entityMetadata->getAssociations());

        /**
         * AssociationNames: category, groups, product
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
        $entityMetadata->renameAssociation('groups', 'newGroups');

        $this->assertCount(2, $entityMetadata->getAssociations());
        $this->assertEquals(
            $this->getMetadata('Association', 'groups', 'newGroups'),
            $entityMetadata->getAssociation('newGroups')
        );

        $this->assertFalse($entityMetadata->hasProperty('category'));
        $this->assertTrue($entityMetadata->hasProperty('newGroups'));

        $this->assertSame(
            [
                'identifiers' => [],
                'associations'      => [
                    'products' => [],
                    'newGroups' => [],
                ]
            ],
            $entityMetadata->toArray()
        );

        $entityMetadata->renameProperty('products', 'newProducts');

        $this->assertFalse($entityMetadata->hasProperty('products'));
        $this->assertFalse($entityMetadata->hasAssociation('products'));
        $this->assertTrue($entityMetadata->hasProperty('newProducts'));
        $this->assertTrue($entityMetadata->hasAssociation('newProducts'));

        $entityMetadata->removeProperty('newProducts');

        $this->assertFalse($entityMetadata->hasProperty('newProducts'));
        $this->assertFalse($entityMetadata->hasAssociation('newProducts'));

        $this->assertEmpty($entityMetadata->getFields());
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
