<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Strategy;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\DefaultAccessor;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\DelegateAccessor;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\InverseAssociationAccessor;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;
use Oro\Bundle\EntityMergeBundle\Model\Strategy\UniteStrategy;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\CollectionItemStub;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;

class UniteStrategyTest extends \PHPUnit_Framework_TestCase
{
    public function testNotSupports()
    {
        $fieldData = $this->createFieldData(
            $this->createEntityData(),
            ['merge_modes' => [MergeModes::REPLACE]]
        );

        $strategy = $this->getStrategy([]);

        $this->assertFalse($strategy->supports($fieldData));
    }

    public function testMergeManyToOne()
    {
        $masterEntity = new EntityStub(1);
        $sourceEntity = new EntityStub(2);
        $item1 = new CollectionItemStub(1);
        $item2 = new CollectionItemStub(2);
        $item1->setEntityStub($masterEntity);
        $item2->setEntityStub($sourceEntity);

        $doctrineMetadata = [
            'fieldName' => 'entityStub',
            'targetEntity' => EntityStub::class,
            'type' => ClassMetadataInfo::MANY_TO_ONE,
        ];

        $fieldMetadata = [
            'field_name' => 'collection',
            'merge_modes' => [MergeModes::UNITE],
            'source_class_name' => EntityStub::class,
        ];

        $entityData = $this->createEntityData(
            $masterEntity,
            $sourceEntity,
            ['name' => CollectionItemStub::class]
        );

        $fieldData = $this->createFieldData($entityData, $fieldMetadata, $doctrineMetadata);
        $strategy = $this->getStrategy([$item1, $item2]);

        $this->assertTrue($strategy->supports($fieldData));

        $strategy->merge($fieldData);

        $this->assertSame($masterEntity, $item1->getEntityStub());
        $this->assertSame($masterEntity, $item2->getEntityStub());
    }

    public function testMergeOneToMany()
    {
        $masterEntity = new EntityStub(1);
        $sourceEntity = new EntityStub(2);
        $collectionItem1 = new CollectionItemStub(1);
        $collectionItem2 = new CollectionItemStub(2);
        $masterEntity->addCollectionItem($collectionItem1);
        $sourceEntity->addCollectionItem($collectionItem2);

        $doctrineMetadata = [
            'fieldName' => 'collection',
            'targetEntity' => CollectionItemStub::class,
            'type' => ClassMetadataInfo::ONE_TO_MANY,
            'orphanRemoval' => true,
        ];

        $fieldMetadata = [
            'field_name' => 'collection',
            'merge_modes' => [MergeModes::UNITE],
        ];

        $entityData = $this->createEntityData(
            $masterEntity,
            $sourceEntity,
            ['name' => EntityStub::class]
        );

        $fieldData = $this->createFieldData($entityData, $fieldMetadata, $doctrineMetadata);
        $strategy = $this->getStrategy([$collectionItem1, $collectionItem2]);

        $this->assertTrue($strategy->supports($fieldData));

        $strategy->merge($fieldData);
        $collection = $masterEntity->getCollection();

        $this->assertCount(2, $collection);
        $this->assertEquals(1, $collection[0]->getId());
        $this->assertEquals(2, $collection[1]->getId());
        $this->assertNotSame($collection[0], $collectionItem1);
        $this->assertNotSame($collection[1], $collectionItem2);
    }

    public function testMergeManyToMany()
    {
        $masterEntity = new EntityStub(1);
        $sourceEntity = new EntityStub(2);
        $collectionItem1 = new CollectionItemStub(1);
        $collectionItem2 = new CollectionItemStub(2);
        $masterEntity->addCollectionItem($collectionItem1);
        $sourceEntity->addCollectionItem($collectionItem2);

        $doctrineMetadata = [
            'fieldName' => 'collection',
            'targetEntity' => CollectionItemStub::class,
            'type' => ClassMetadataInfo::MANY_TO_MANY,
        ];

        $fieldMetadata = [
            'field_name' => 'collection',
            'merge_modes' => [MergeModes::UNITE],
        ];

        $entityData = $this->createEntityData(
            $masterEntity,
            $sourceEntity,
            ['name' => EntityStub::class]
        );

        $fieldData = $this->createFieldData($entityData, $fieldMetadata, $doctrineMetadata);
        $strategy = $this->getStrategy([$collectionItem1, $collectionItem2]);

        $this->assertTrue($strategy->supports($fieldData));

        $strategy->merge($fieldData);
        $collection = $masterEntity->getCollection();

        $this->assertCount(2, $collection);
        $this->assertTrue($collection->contains($collectionItem1));
        $this->assertTrue($collection->contains($collectionItem2));
    }

    /**
     * @param  array  $relatedEntities
     * @return UniteStrategy
     */
    private function getStrategy(array $relatedEntities)
    {
        $repository = $this
            ->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository
            ->expects($this->any())
            ->method('findBy')
            ->will(
                $this->returnCallback(
                    function ($values) use ($relatedEntities) {
                        return [
                            $relatedEntities[$values['entityStub']->getId()-1]
                        ];
                    }
                )
            );

        $doctrineHelper = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineHelper->expects($this->any())
            ->method('getEntityIdentifierValue')
            ->will(
                $this->returnCallback(
                    function ($value) {
                        return $value->getId();
                    }
                )
            );

        $doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->will($this->returnValue($repository));

        $accessor = new DelegateAccessor([
            new InverseAssociationAccessor($doctrineHelper),
            new DefaultAccessor(),
        ]);

        return new UniteStrategy($accessor, $doctrineHelper);
    }

    /**
     * @param  EntityData $entityData
     * @param  array      $metadata
     * @param  array      $doctrineMetadata
     * @return FieldData
     */
    private function createFieldData(EntityData $entityData, array $metadata, array $doctrineMetadata = [])
    {
        $doctrineMetadata = new DoctrineMetadata($doctrineMetadata);
        $fieldMetadata = new FieldMetadata($metadata, $doctrineMetadata);
        $fieldMetadata->setEntityMetadata($entityData->getMetadata());
        $fieldData = new FieldData($entityData, $fieldMetadata);

        return $fieldData;
    }

    /**
     * @param  object $masterEntity
     * @param  object $sourceEntity
     * @param  array  $doctrineMetadata
     * @return EntityData
     */
    private function createEntityData($masterEntity = null, $sourceEntity = null, array $doctrineMetadata = [])
    {
        $doctrineMetadata = new DoctrineMetadata($doctrineMetadata);
        $metadata = new EntityMetadata([], $doctrineMetadata);
        $entityData = new EntityData($metadata, [$masterEntity, $sourceEntity]);
        $entityData->setMasterEntity($masterEntity);

        return $entityData;
    }
}
