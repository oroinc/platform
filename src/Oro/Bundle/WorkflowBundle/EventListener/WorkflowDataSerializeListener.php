<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer;
use Oro\Component\DependencyInjection\ServiceLink;

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
     * @var WorkflowItem[]
     */
    protected $scheduledEntities = [];

    /** @var ServiceLink */
    private $serializerLink;

    /**
     * @param ServiceLink $serializerLink
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(ServiceLink $serializerLink, DoctrineHelper $doctrineHelper)
    {
        $this->serializerLink = $serializerLink;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Before flush serializes all WorkflowItem's data
     *
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        /** @var WorkflowItem $entity */
        $unitOfWork = $args->getEntityManager()->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if ($this->isSupported($entity) && $entity->getData()->isModified()) {
                $this->scheduledEntities[] = $entity;
            }
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($this->isSupported($entity) && $entity->getData()->isModified()) {
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
            while ($workflowItem = array_shift($this->scheduledEntities)) {
                $this->serialize($workflowItem);
            }
            $args->getEntityManager()->flush();
        }
    }

    /**
     * After WorkflowItem loaded, deserialize WorkflowItem
     *
     * @param WorkflowItem       $entity
     * @param LifecycleEventArgs $args
     */
    public function postLoad(WorkflowItem $entity, LifecycleEventArgs $args)
    {
        $this->deserialize($entity);
    }

    /**
     * Serialize data of WorkflowItem
     *
     * @param WorkflowItem $workflowItem
     */
    protected function serialize(WorkflowItem $workflowItem)
    {
        $serializer = $this->getSerializer();
        $serializer->setWorkflowName($workflowItem->getWorkflowName());

        $serializedData = $serializer->serialize(
            $this->getWorkflowData($workflowItem),
            $this->format
        );
        $workflowItem->setSerializedData($serializedData);
        $workflowItem->getData()->setModified(false);
    }

    /**
     * Deserialize data of WorkflowItem
     *
     * @param WorkflowItem $workflowItem
     */
    protected function deserialize(WorkflowItem $workflowItem)
    {
        // Pass serializer into $workflowItem to make lazy loading of workflow item data.
        $workflowItem->setSerializer($this->getSerializer(), $this->format);

        // Set related entity
        $relatedEntity = $this->doctrineHelper->getEntityReference(
            $workflowItem->getDefinition()->getRelatedEntity(),
            $workflowItem->getEntityId()
        );
        $workflowItem->setEntity($relatedEntity);
    }

    /**
     * @param WorkflowItem $workflowItem
     * @return WorkflowData
     */
    protected function getWorkflowData(WorkflowItem $workflowItem)
    {
        // Cloning workflow data instance to prevent changing of original data.
        $workflowData = clone $workflowItem->getData();

        // entity attribute must not be serialized
        $workflowData->remove($workflowItem->getDefinition()->getEntityAttributeName());

        $virtualAttributes = array_keys($workflowItem->getDefinition()->getVirtualAttributes());
        foreach ($virtualAttributes as $attributeName) {
            $workflowData->remove($attributeName);
        }

        // workflow attributes must not be serialized
        $workflowConfig = $workflowItem->getDefinition()->getConfiguration();
        $variableNames = $this->getVariablesNamesFromConfiguration($workflowConfig);
        foreach ($variableNames as $variableName) {
            $workflowData->remove($variableName);
        }

        return $workflowData;
    }

    /**
     * @param $entity
     * @return bool
     */
    protected function isSupported($entity)
    {
        return $entity instanceof WorkflowItem;
    }

    /**
     * @param array $configuration
     *
     * @return array
     */
    protected function getVariablesNamesFromConfiguration($configuration)
    {
        $definitionsNode = WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS;
        $variablesNode = WorkflowConfiguration::NODE_VARIABLES;

        if (!is_array($configuration) || !isset($configuration[$definitionsNode][$variablesNode])) {
            return [];
        }

        return array_keys($configuration[$definitionsNode][$variablesNode]);
    }

    /**
     * @return WorkflowAwareSerializer
     */
    private function getSerializer()
    {
        return $this->serializerLink->getService();
    }
}
