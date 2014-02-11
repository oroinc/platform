<?php

namespace Oro\Bundle\TagBundle\EventListener;

use Oro\Bundle\EntityMergeBundle\Event\AfterMergeEvent;
use Oro\Bundle\EntityMergeBundle\Event\BeforeMergeEvent;
use Oro\Bundle\EntityMergeBundle\Event\CreateEntityDataEvent;
use Oro\Bundle\EntityMergeBundle\Event\CreateMetadataEvent;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
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
     * @param CreateMetadataEvent $event
     */
    public function onCreateMetadata(CreateMetadataEvent $event)
    {
        $entityMetadata = $event->getEntityMetadata();
        if (!$this->isTaggable($entityMetadata)) {
            return;
        }

        $fieldMetadataOptions = [
            'getter'        => self::GETTER,
            'setter'        => self::SETTER,
            'field_name'    => self::FIELD_NAME,
            'is_collection' => true
        ];
        $fieldMetadata        = new FieldMetadata($fieldMetadataOptions);
        $entityMetadata->addFieldMetadata($fieldMetadata);
    }

    /**
     * @param BeforeMergeEvent $event
     */
    public function onCreateEntityData(CreateEntityDataEvent $event)
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
     * @param AfterMergeEvent $event
     */
    public function afterMerge(AfterMergeEvent $event)
    {
        $entityData     = $event->getEntityData();
        $entityMetadata = $entityData->getMetadata();
        if (!$this->isTaggable($entityMetadata)) {
            return;
        }

        $masterEntity = $entityData->getMasterEntity();
        $this->manager->saveTagging($masterEntity, false);
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
