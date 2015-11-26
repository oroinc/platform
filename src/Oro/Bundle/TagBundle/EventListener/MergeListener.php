<?php

namespace Oro\Bundle\TagBundle\EventListener;

use Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent;
use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;
use Oro\Bundle\TagBundle\Entity\TagManager;

class MergeListener
{
    const FIELD_NAME = 'tags';

    /** @var TagManager */
    protected $tagManager;

    /**
     * @param TagManager $tagManager
     */
    public function __construct(TagManager $tagManager)
    {
        $this->tagManager = $tagManager;
    }

    /**
     * Add merge metadata for tags
     *
     * @param EntityMetadataEvent $event
     */
    public function onBuildMetadata(EntityMetadataEvent $event)
    {
        $entityMetadata = $event->getEntityMetadata();
        if (!$this->isTaggable($entityMetadata)) {
            return;
        }

        $fieldMetadataOptions = [
            'source_class_name' => 'Oro\Bundle\TagBundle\Entity\Tag',
            'display'           => true,
            'field_name'        => self::FIELD_NAME,
            'is_collection'     => true,
            'label'             => 'oro.tag.entity_plural_label',
            'merge_modes'       => [MergeModes::REPLACE, MergeModes::UNITE]
        ];

        $fieldMetadata = new FieldMetadata($fieldMetadataOptions);
        $entityMetadata->addFieldMetadata($fieldMetadata);
    }

    /**
     * Load tags
     *
     * @param EntityDataEvent $event
     */
    public function onCreateEntityData(EntityDataEvent $event)
    {
        $entityData     = $event->getEntityData();
        $entityMetadata = $entityData->getMetadata();
        if (!$this->isTaggable($entityMetadata)) {
            return;
        }

        $entities = $entityData->getEntities();
        foreach ($entities as $entity) {
            $this->tagManager->loadTagging($entity);
        }
    }

    /**
     * Save tags
     *
     * @param EntityDataEvent $event
     */
    public function afterMergeEntity(EntityDataEvent $event)
    {
        $entityData     = $event->getEntityData();
        $entityMetadata = $entityData->getMetadata();
        if (!$this->isTaggable($entityMetadata)) {
            return;
        }

        $masterEntity = $entityData->getMasterEntity();
        $masterTags   = $this->tagManager->getTags($masterEntity)->getValues();
        $this->tagManager->setTags($masterEntity, ['all' => $masterTags, 'owner' => $masterTags]);
        $this->tagManager->saveTagging($masterEntity);
    }

    /**
     * @param EntityMetadata $entityMetadata
     *
     * @return bool
     */
    private function isTaggable(EntityMetadata $entityMetadata)
    {
        $className = $entityMetadata->getClassName();

        return $this->tagManager->isTaggable($className);
    }
}
