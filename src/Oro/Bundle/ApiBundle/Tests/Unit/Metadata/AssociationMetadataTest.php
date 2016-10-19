<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class AssociationMetadataTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityMetadata */
    protected $entityMetadata;

    protected function setUp()
    {
        $this->entityMetadata = new EntityMetadata();
        $this->entityMetadata->setClassName('entityClassName');
        $this->entityMetadata->setInheritedType(true);
    }

    public function testClone()
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName('testName');
        $associationMetadata->setPropertyPath('testPropertyPath');
        $associationMetadata->setDataType('testDataType');
        $associationMetadata->setTargetClassName('targetClassName');
        $associationMetadata->setAcceptableTargetClassNames(['targetClassName1']);
        $associationMetadata->setIsCollection(true);
        $associationMetadata->setIsNullable(true);
        $associationMetadata->setCollapsed(true);
        $targetEntityMetadata = new EntityMetadata();
        $targetEntityMetadata->setClassName('TargetEntityClassName');
        $associationMetadata->setTargetMetadata($targetEntityMetadata);

        $associationMetadataClone = clone $associationMetadata;

        $this->assertEquals($associationMetadata, $associationMetadataClone);
        $this->assertNotSame($targetEntityMetadata, $associationMetadataClone->getTargetMetadata());
    }

    public function testCloneWithoutTargetMetadata()
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName('testName');

        $associationMetadataClone = clone $associationMetadata;

        $this->assertEquals($associationMetadata, $associationMetadataClone);
        $this->assertNull($associationMetadataClone->getTargetMetadata());
    }

    public function testToArray()
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName('testName');
        $associationMetadata->setPropertyPath('testPropertyPath');
        $associationMetadata->setDataType('testDataType');
        $associationMetadata->setTargetClassName('targetClassName');
        $associationMetadata->setAcceptableTargetClassNames(['targetClassName1', 'targetClassName2']);
        $associationMetadata->setAssociationType('manyToMany');
        $associationMetadata->setIsCollection(true);
        $associationMetadata->setIsNullable(true);
        $associationMetadata->setCollapsed(true);
        $associationMetadata->setTargetMetadata($this->entityMetadata);

        $this->assertEquals(
            [
                'name'                      => 'testName',
                'property_path'             => 'testPropertyPath',
                'data_type'                 => 'testDataType',
                'nullable'                  => true,
                'collapsed'                 => true,
                'association_type'          => 'manyToMany',
                'collection'                => true,
                'target_class'              => 'targetClassName',
                'acceptable_target_classes' => ['targetClassName1', 'targetClassName2'],
                'target_metadata'           => [
                    'class'     => 'entityClassName',
                    'inherited' => true
                ]
            ],
            $associationMetadata->toArray()
        );
    }

    public function testToArrayWithRequiredPropertiesOnly()
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName('testName');

        $this->assertEquals(
            [
                'name'             => 'testName',
                'nullable'         => false,
                'collapsed'        => false,
                'association_type' => null,
                'collection'       => false,
            ],
            $associationMetadata->toArray()
        );
    }

    public function testNameInConstructor()
    {
        $fieldMetadata = new AssociationMetadata('associationName');
        $this->assertEquals('associationName', $fieldMetadata->getName());
    }

    public function testName()
    {
        $associationMetadata = new AssociationMetadata();

        $this->assertNull($associationMetadata->getName());
        $associationMetadata->setName('associationName');
        $this->assertEquals('associationName', $associationMetadata->getName());
    }

    public function testPropertyPath()
    {
        $associationMetadata = new AssociationMetadata();

        $this->assertNull($associationMetadata->getPropertyPath());
        $associationMetadata->setName('name');
        $this->assertEquals('name', $associationMetadata->getPropertyPath());
        $associationMetadata->setPropertyPath('propertyPath');
        $this->assertEquals('propertyPath', $associationMetadata->getPropertyPath());
        $associationMetadata->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $this->assertNull($associationMetadata->getPropertyPath());
        $associationMetadata->setPropertyPath(null);
        $this->assertEquals('name', $associationMetadata->getPropertyPath());
    }

    public function testDataType()
    {
        $associationMetadata = new AssociationMetadata();

        $this->assertNull($associationMetadata->getDataType());
        $associationMetadata->setDataType('associationType');
        $this->assertEquals('associationType', $associationMetadata->getDataType());
    }

    public function testTargetClassName()
    {
        $associationMetadata = new AssociationMetadata();

        $this->assertNull($associationMetadata->getTargetClassName());
        $associationMetadata->setTargetClassName('targetClassName');
        $this->assertEquals('targetClassName', $associationMetadata->getTargetClassName());
    }

    public function testAcceptableTargetClassName()
    {
        $associationMetadata = new AssociationMetadata();

        $this->assertEquals([], $associationMetadata->getAcceptableTargetClassNames());
        $associationMetadata->setAcceptableTargetClassNames(['targetClassName1', 'targetClassName2']);
        $this->assertEquals(
            ['targetClassName1', 'targetClassName2'],
            $associationMetadata->getAcceptableTargetClassNames()
        );
        $associationMetadata->removeAcceptableTargetClassName('targetClassName1');
        $associationMetadata->addAcceptableTargetClassName('targetClassName3');
        $this->assertEquals(
            ['targetClassName2', 'targetClassName3'],
            $associationMetadata->getAcceptableTargetClassNames()
        );
    }

    public function testAssociationType()
    {
        $associationMetadata = new AssociationMetadata();

        $this->assertNull($associationMetadata->getAssociationType());
        $associationMetadata->setAssociationType('manyToOne');
        $this->assertEquals('manyToOne', $associationMetadata->getAssociationType());
    }

    public function testCollection()
    {
        $associationMetadata = new AssociationMetadata();

        $this->assertFalse($associationMetadata->isCollection());
        $associationMetadata->setIsCollection(true);
        $this->assertTrue($associationMetadata->isCollection());
    }

    public function testNullable()
    {
        $associationMetadata = new AssociationMetadata();

        $this->assertFalse($associationMetadata->isNullable());
        $associationMetadata->setIsNullable(true);
        $this->assertTrue($associationMetadata->isNullable());
    }

    public function testCollapsed()
    {
        $associationMetadata = new AssociationMetadata();

        $this->assertFalse($associationMetadata->isCollapsed());
        $associationMetadata->setCollapsed(true);
        $this->assertTrue($associationMetadata->isCollapsed());
    }

    public function testTargetMetadata()
    {
        $associationMetadata = new AssociationMetadata();

        $this->assertNull($associationMetadata->getTargetMetadata());
        $associationMetadata->setTargetMetadata($this->entityMetadata);
        $this->assertEquals($this->entityMetadata, $associationMetadata->getTargetMetadata());
    }
}
