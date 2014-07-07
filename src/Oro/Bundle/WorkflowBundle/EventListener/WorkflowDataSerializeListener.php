<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer;

/**
 * Performs serialization and deserialization of WorkflowItem data
 */
class WorkflowDataSerializeListener
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $format = 'json';

    /**
     * @var bool
     */
    protected $needFlush = false;

    /**
     * @var WorkflowItem[]
     */
    protected $scheduledEntities;

    /**
     * @var WorkflowAwareSerializer
     */
    protected $serializer;

    /**
     * Constructor
     *
     * @param WorkflowAwareSerializer $serializer
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(WorkflowAwareSerializer $serializer, DoctrineHelper $doctrineHelper)
    {
        $this->serializer = $serializer;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Before flush serializes all WorkflowItem's data
     *
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $unitOfWork = $args->getEntityManager()->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if ($this->isSupported($entity)) {
                $this->scheduledEntities[] = $entity;
            }
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($this->isSupported($entity)) {
                $this->scheduledEntities[] = $entity;
            }
        }
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if ($this->scheduledEntities) {
            $this->needFlush = false;
            $entityManager   = $args->getEntityManager();
            $unitOfWork      = $entityManager->getUnitOfWork();

            while ($workflowItem = array_shift($this->scheduledEntities)) {
                $this->serialize($workflowItem, $unitOfWork);
            }

            if ($this->needFlush) {
                $entityManager->flush();
            }
        }
    }

    /**
     * After WorkflowItem loaded, deserialize WorkflowItem
     *
     * @param LifecycleEventArgs $args
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        /** @var WorkflowItem $entity */
        $entity = $args->getEntity();
        if ($this->isSupported($entity)) {
            $this->deserialize($entity);
        }
    }

    /**
     * Serialize data of WorkflowItem
     *
     * @param WorkflowItem $workflowItem
     * @param UnitOfWork   $unitOfWork
     */
    protected function serialize(WorkflowItem $workflowItem, UnitOfWork $unitOfWork)
    {
        $workflowData = $workflowItem->getData();

        if ($workflowData->isModified()) {
            $oldSerializedData = $workflowItem->getSerializedData();

            $this->serializer->setWorkflowName($workflowItem->getWorkflowName());

            // Cloning workflow data instance to prevent changing of original data.
            $workflowData = clone $workflowData;
            // entity attribute must not be serialized
            $workflowData->remove($workflowItem->getDefinition()->getEntityAttributeName());

            $newSerializedData = $this->serializer->serialize($workflowData, $this->format);

            $workflowItem->setSerializedData($newSerializedData);

            if ($newSerializedData != $oldSerializedData) {
                $unitOfWork->propertyChanged($workflowItem, 'serializedData', $oldSerializedData, $newSerializedData);
                $this->needFlush = true;
            }
        }
    }

    /**
     * Deserialize data of WorkflowItem
     *
     * @param WorkflowItem $workflowItem
     */
    protected function deserialize(WorkflowItem $workflowItem)
    {
        // Pass serializer into $workflowItem to make lazy loading of workflow item data.
        $workflowItem->setSerializer($this->serializer, $this->format);

        // Set related entity
        $relatedEntity = $this->doctrineHelper->getEntityReference(
            $workflowItem->getDefinition()->getRelatedEntity(),
            $workflowItem->getEntityId()
        );
        $workflowItem->setEntity($relatedEntity);
    }

    /**
     * @param $entity
     * @return bool
     */
    protected function isSupported($entity)
    {
        return $entity instanceof WorkflowItem;
    }
}
