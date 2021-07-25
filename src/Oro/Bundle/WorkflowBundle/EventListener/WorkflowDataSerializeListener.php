<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Performs serialization and deserialization of WorkflowItem data.
 */
class WorkflowDataSerializeListener implements ServiceSubscriberInterface
{
    private ContainerInterface $container;
    private DoctrineHelper $doctrineHelper;
    private string $format = 'json';
    /** @var WorkflowItem[] */
    private array $scheduledEntities = [];

    public function __construct(ContainerInterface $container, DoctrineHelper $doctrineHelper)
    {
        $this->container = $container;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Before flush serializes all WorkflowItem's data
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $uow = $args->getEntityManager()->getUnitOfWork();
        $this->scheduleEntities($uow->getScheduledEntityInsertions());
        $this->scheduleEntities($uow->getScheduledEntityUpdates());
    }

    public function postFlush(PostFlushEventArgs $args): void
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
     */
    public function postLoad(WorkflowItem $entity): void
    {
        $this->deserialize($entity);
    }

    private function scheduleEntities(array $entities): void
    {
        foreach ($entities as $entity) {
            if ($this->isSupported($entity) && $entity->getData()->isModified()) {
                $this->scheduledEntities[] = $entity;
            }
        }
    }

    /**
     * Serialize data of WorkflowItem
     */
    private function serialize(WorkflowItem $workflowItem): void
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
     */
    private function deserialize(WorkflowItem $workflowItem): void
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

    private function getWorkflowData(WorkflowItem $workflowItem): WorkflowData
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

    private function isSupported(object $entity): bool
    {
        return $entity instanceof WorkflowItem;
    }

    private function getVariablesNamesFromConfiguration(?array $configuration): array
    {
        $definitionsNode = WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS;
        $variablesNode = WorkflowConfiguration::NODE_VARIABLES;

        if (!\is_array($configuration) || !isset($configuration[$definitionsNode][$variablesNode])) {
            return [];
        }

        return array_keys($configuration[$definitionsNode][$variablesNode]);
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_workflow.serializer.data.serializer' => WorkflowAwareSerializer::class
        ];
    }

    private function getSerializer(): WorkflowAwareSerializer
    {
        return $this->container->get('oro_workflow.serializer.data.serializer');
    }
}
