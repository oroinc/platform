<?php

namespace Oro\Bundle\TagBundle\EventListener;

use Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent;
use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;
use Oro\Bundle\TagBundle\Entity\Taggable;
use Oro\Bundle\TagBundle\Entity\TagManager;

class MergeListener
{
    const GETTER     = 'getTags';
    const SETTER     = 'setTags';
    const FIELD_NAME = 'tags';

    /**
     * @var TagManager
     */
    protected $manager;

    /**
     * @param TagManager $manager
     */
    public function __construct(TagManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Add merge metadata for tags
     *
     * @param EntityMetadataEvent $event
     */
    public function onCreateMetadata(EntityMetadataEvent $event)
    {
        $entityMetadata = $event->getEntityMetadata();
        if (!$this->isTaggable($entityMetadata)) {
            return;
        }

        $fieldMetadataOptions = [
            'getter'        => self::GETTER,
            'setter'        => self::SETTER,
            'field_name'    => self::FIELD_NAME,
            'is_collection' => true,
            'merge_modes'   => [MergeModes::REPLACE, MergeModes::UNITE]
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
            /* @var Taggable $entity */
            $this->manager->loadTagging($entity);
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

        /* @var Taggable $masterEntity */
        $masterEntity = $entityData->getMasterEntity();
        $masterTags   = $masterEntity->getTags()->getValues();
        $masterEntity->setTags(['all' => $masterTags, 'owner' => $masterTags]);
        $this->manager->saveTagging($masterEntity);
    }

    /**
     * @param EntityMetadata $entityMetadata
     * @return bool
     */
    private function isTaggable(EntityMetadata $entityMetadata)
    {
        $className       = $entityMetadata->getClassName();
        $classInterfaces = class_implements($className);

        if (isset($classInterfaces['Oro\Bundle\TagBundle\Entity\Taggable'])) {
            return true;
        }

        return false;
    }
}
