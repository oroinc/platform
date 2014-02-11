<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Strategy;

use Oro\Bundle\EntityMergeBundle\Model\Accessor\RelationAccessor;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;
use Oro\Bundle\EntityMergeBundle\Model\Strategy\MergeStategy;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;

class MergeStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MergeStategy $strategy
     */
    protected $strategy;

    /**
     * @var
     */
    protected $entityManager;

    public function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getMetadataFactory', 'getRepository'])
            ->getMock();

        $accessor = new RelationAccessor($this->entityManager);

        $this->strategy = new MergeStategy($accessor, $this->entityManager);
    }

    public function testNotSupports()
    {
        $fieldData = $this->createFieldData();
        $fieldData
            ->expects($this->once())
            ->method('getMode')
            ->will($this->returnValue(MergeModes::REPLACE));

        $this->assertFalse($this->strategy->supports($fieldData));
    }

    public function testSupports()
    {
        $fieldData         = $this->createFieldData();
        $fieldMetadataData = $this->createFieldMetadata();
        $doctrineMetadata  = $this->createDoctrineMetadata();

        $fieldData
            ->expects($this->once())
            ->method('getMode')
            ->will($this->returnValue(MergeModes::MERGE));

        $fieldMetadataData
            ->expects($this->once())
            ->method('getDoctrineMetadata')
            ->will($this->returnValue($doctrineMetadata));

        $fieldData
            ->expects($this->once())
            ->method('getMetadata')
            ->will($this->returnValue($fieldMetadataData));

        $this->assertTrue($this->strategy->supports($fieldData));
    }

    public function testMerge()
    {
        $fieldData         = $this->createFieldData();
        $fieldMetadataData = $this->createFieldMetadata();
        $entityData        = $this->createEntityData();
        $masterEntity      = new EntityStub(1);
        $sourceEntity      = new EntityStub(2);

        $entities = [$masterEntity, $sourceEntity];

        $fieldData
            ->expects($this->once())
            ->method('getEntityData')
            ->will($this->returnValue($entityData));

        $fieldData
            ->expects($this->once())
            ->method('getMetadata')
            ->will($this->returnValue($fieldMetadataData));

        $entityData
            ->expects($this->once())
            ->method('getEntities')
            ->will($this->returnValue($entities));

        $this->doctrineMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $metadataFactory = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataFactory')
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $metadataFactory->expects($this->any())
            ->method('getMetadataFor')
            ->will($this->returnValue($this->doctrineMetadata));

        $this->doctrineMetadata->expects($this->any())
            ->method('getIdentifierValues')
            ->will($this->returnValue([1]));

        $this->entityManager->expects($this->any())
            ->method('getMetadataFactory')
            ->will($this->returnValue($metadataFactory));

        $doctrineMetadata = $this->createDoctrineMetadata();
        $fieldMetadataData
            ->expects($this->any())
            ->method('getDoctrineMetadata')
            ->will($this->returnValue($doctrineMetadata));

        $fieldMetadataData
            ->expects($this->any())
            ->method('getFieldName')
            ->will($this->returnValue('id'));

        $this->repository = $this
            ->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($this->repository));

        $this->repository
            ->expects($this->any())
            ->method('findBy')
            ->will($this->returnValue($entities));

        $this->strategy->merge($fieldData);
    }

    protected function createFieldData()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\FieldData')
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createFieldMetadata()
    {
        return $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata')
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createDoctrineMetadata()
    {
        return $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createEntityData()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\EntityData')
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();
    }
}
