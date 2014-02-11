<?php

namespace Oro\Bundle\EntityMergeBundle\Data;

use Oro\Bundle\EntityMergeBundle\Metadata\MetadataFactory;

class EntityDataFactory
{
    /**
     * @var MetadataFactory $metadataFactory
     */
    private $metadataFactory;

    /**
     * @var EntityProvider $entityProvider
     */
    private $entityProvider;

    /**
     * @param MetadataFactory $metadataFactory
     * @param EntityProvider $entityProvider
     */
    public function __construct(MetadataFactory $metadataFactory, EntityProvider $entityProvider)
    {
        $this->metadataFactory = $metadataFactory;
        $this->entityProvider = $entityProvider;
    }

    /**
     * @param string $entityName
     * @param array $entities
     * @return EntityData
     */
    public function createEntityData($entityName, array $entities)
    {
        $entityMetadata = $this->metadataFactory->createMergeMetadata($entityName);

        $data = new EntityData($entityMetadata);
        $data->setEntities($entities);
        foreach ($entityMetadata->getFieldsMetadata() as $fieldMetadata) {
            $data->addNewField($fieldMetadata);
        }

        return $data;
    }

    /**
     * @param string $entityName
     * @param array $entityIds
     * @return EntityData
     */
    public function createEntityDataByIds($entityName, array $entityIds)
    {
        $entities = $this->entityProvider->getEntitiesByIds($entityName, $entityIds);
        return $this->createEntityData($entityName, $entities);
    }
}
