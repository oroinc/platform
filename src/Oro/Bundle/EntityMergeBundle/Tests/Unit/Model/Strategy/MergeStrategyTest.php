<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Strategy;

use Oro\Bundle\EntityMergeBundle\Model\Accessor\InverseAssociationAccessor;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;
use Oro\Bundle\EntityMergeBundle\Model\Strategy\MergeStrategy;

use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\CollectionItemStub;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;

class MergeStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MergeStrategy $strategy
     */
    protected $strategy;

    /**
     * @var \PHPUnit_Framework_TestCase
     */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->doctrineHelper = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $accessor = new InverseAssociationAccessor($this->doctrineHelper);

        $this->strategy = new MergeStrategy($accessor, $this->doctrineHelper);
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

        $fieldData
            ->expects($this->exactly(2))
            ->method('getMode')
            ->will($this->returnValue(MergeModes::MERGE));

        $fieldMetadataData
            ->expects($this->at(0))
            ->method('isCollection')
            ->will($this->returnValue(false));

        $fieldMetadataData
            ->expects($this->at(1))
            ->method('isCollection')
            ->will($this->returnValue(true));

        $fieldData
            ->expects($this->exactly(2))
            ->method('getMetadata')
            ->will($this->returnValue($fieldMetadataData));

        $this->assertFalse($this->strategy->supports($fieldData));
        $this->assertTrue($this->strategy->supports($fieldData));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testMerge()
    {
        $fieldData         = $this->createFieldData();
        $fieldMetadataData = $this->createFieldMetadata();
        $entityData        = $this->createEntityData();
        $masterEntity      = new EntityStub(1);
        $sourceEntity      = new EntityStub(2);
        $collectionItem1   = new CollectionItemStub(1);
        $collectionItem2   = new CollectionItemStub(2);
        $masterEntity->addCollectionItem($collectionItem1);
        $sourceEntity->addCollectionItem($collectionItem2);

        $entities = [$masterEntity, $sourceEntity];
        $relatedEntities = [$collectionItem1, $collectionItem2];

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

        $entityData
            ->expects($this->once())
            ->method('getMasterEntity')
            ->will($this->returnValue($masterEntity));

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityIdentifierValue')
            ->will(
                $this->returnCallback(
                    function ($value) {
                        return $value->getId();
                    }
                )
            );

        $fieldDoctrineMetadata = $this->createDoctrineMetadata();
        $fieldDoctrineMetadata->expects($this->any())
            ->method('getFieldName')
            ->will($this->returnValue('field_name'));

        $fieldMetadataData
            ->expects($this->any())
            ->method('getDoctrineMetadata')
            ->will($this->returnValue($fieldDoctrineMetadata));

        $fieldMetadataData
            ->expects($this->any())
            ->method('getFieldName')
            ->will($this->returnValue('collection'));

        $fieldMetadataData
            ->expects($this->any())
            ->method('has')
            ->will($this->returnValue(true));

        $fieldMetadataData
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValue('setEntityStub'));

        $repository = $this
            ->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->will($this->returnValue($repository));

        $repository
            ->expects($this->any())
            ->method('findBy')
            ->will(
                $this->returnCallback(
                    function ($values) use ($relatedEntities) {
                        return [$relatedEntities[$values['field_name']->getId()-1]];
                    }
                )
            );

        $this->strategy->merge($fieldData);

        $this->assertEquals($masterEntity, $collectionItem1->getEntityStub());
        $this->assertEquals($masterEntity, $collectionItem2->getEntityStub());
    }

    public function testGetName()
    {
        $this->assertEquals('merge', $this->strategy->getName());
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
