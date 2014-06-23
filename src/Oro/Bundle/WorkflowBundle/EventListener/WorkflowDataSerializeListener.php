<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
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
     * @var WorkflowAwareSerializer
     */
    protected $serializer;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $format = 'json';

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
                $this->serialize($entity, $unitOfWork);
            }
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($this->isSupported($entity)) {
                $this->serialize($entity, $unitOfWork);
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
     * @param UnitOfWork $uow
     */
    protected function serialize(WorkflowItem $workflowItem, UnitOfWork $uow)
    {
        $workflowData = $workflowItem->getData();
        if ($workflowData->isModified()) {
            $oldValue = $workflowItem->getSerializedData();

            $this->serializer->setWorkflowName($workflowItem->getWorkflowName());

            // Cloning workflow data instance to prevent changing of original data.
            $workflowData = clone $workflowData;
            // entity attribute must not be serialized
            $workflowData->remove($workflowItem->getDefinition()->getEntityAttributeName());
            $serializedData = $this->serializer->serialize($workflowData, $this->format);
            $workflowItem->setSerializedData($serializedData);

            $uow->propertyChanged($workflowItem, 'serializedData', $oldValue, $serializedData);
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
