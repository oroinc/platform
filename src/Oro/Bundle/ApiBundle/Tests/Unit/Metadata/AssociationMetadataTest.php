<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

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

    public function testGetName()
    {
        $associationMetadata = new AssociationMetadata();

        $this->assertNull($associationMetadata->getName());
        $associationMetadata->setName('associationName');
        $this->assertEquals('associationName', $associationMetadata->getName());
    }

    public function testGetDataType()
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
        $this->assertSame('targetClassName', $associationMetadata->getTargetClassName());
    }

    public function testAcceptableTargetClassName()
    {
        $associationMetadata = new AssociationMetadata();

        $this->assertSame([], $associationMetadata->getAcceptableTargetClassNames());
        $associationMetadata->setAcceptableTargetClassNames(['targetClassName0', 'targetClassName1']);
        $this->assertSame(
            ['targetClassName0', 'targetClassName1'],
            $associationMetadata->getAcceptableTargetClassNames()
        );
        $associationMetadata->removeAcceptableTargetClassName('targetClassName0');
        $associationMetadata->addAcceptableTargetClassName('targetClassName2');
        $this->assertSame(
            ['targetClassName1', 'targetClassName2'],
            $associationMetadata->getAcceptableTargetClassNames()
        );
    }

    public function testIsCollection()
    {
        $associationMetadata = new AssociationMetadata();

        $this->assertFalse($associationMetadata->isCollection());
        $associationMetadata->setIsCollection(true);
        $this->assertTrue($associationMetadata->isCollection());
    }

    public function testIsNullable()
    {
        $associationMetadata = new AssociationMetadata();

        $this->assertFalse($associationMetadata->isNullable());
        $associationMetadata->setIsNullable(true);
        $this->assertTrue($associationMetadata->isNullable());
    }

    public function testTargetMetadata()
    {
        $associationMetadata = new AssociationMetadata();

        $this->assertNull($associationMetadata->getTargetMetadata());
        $associationMetadata->setTargetMetadata($this->entityMetadata);
        $this->assertSame($this->entityMetadata, $associationMetadata->getTargetMetadata());
    }

    public function testToArray()
    {
        $associationMetadata = new AssociationMetadata();

        $associationMetadata->setTargetClassName('targetClassName');
        $associationMetadata->setAcceptableTargetClassNames(['targetClassName1', 'targetClassName2']);
        $associationMetadata->setIsCollection(true);
        $associationMetadata->setTargetMetadata($this->entityMetadata);
        $associationMetadata->setTargetMetadata($this->entityMetadata);

        $this->assertSame(
            [
                AssociationMetadata::TARGET_CLASS_NAME => 'targetClassName',
                AssociationMetadata::ACCEPTABLE_TARGET_CLASS_NAMES => ['targetClassName1', 'targetClassName2'],
                AssociationMetadata::COLLECTION => true,
                'targetMetadata' => [
                    'class' => 'entityClassName',
                    'inherited' => true,
                    'identifiers' => []
                ]
            ],
            $associationMetadata->toArray()
        );
    }
}
