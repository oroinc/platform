<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;

class WorkflowSystemConfigManager
{
    const CONFIG_PROVIDER_NAME = 'workflow';
    const CONFIG_KEY = 'active_workflows';

    /** @var ConfigManager */
    private $configManager;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param ConfigManager $configManager
     * @param EventDispatcherInterface $eventDispatcher
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ConfigManager $configManager,
        EventDispatcherInterface $eventDispatcher,
        DoctrineHelper $doctrineHelper
    ) {
        $this->configManager = $configManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param WorkflowDefinition $definition
     * @return bool
     * @throws WorkflowException
     */
    public function isActiveWorkflow(WorkflowDefinition $definition)
    {
        return in_array(
            $definition->getName(),
            (array)$this->getEntityConfig($definition->getRelatedEntity())->get(self::CONFIG_KEY),
            true
        );
    }

    /**
     * @param object|string $entity An instance of entity or its class name
     * @return string[]
     */
    public function getActiveWorkflowNamesByEntity($entity)
    {
        $class = $this->doctrineHelper->getEntityClass($entity);

        return $this->getEntityConfig($class)->get(self::CONFIG_KEY, false, []);
    }

    /**
     * @param WorkflowDefinition $definition
     * @throws WorkflowException
     */
    public function setWorkflowActive(WorkflowDefinition $definition)
    {
        $this->setWorkflowState($definition, true);
    }

    /**
     * @param WorkflowDefinition $definition
     * @throws WorkflowException
     */
    public function setWorkflowInactive(WorkflowDefinition $definition)
    {
        $this->setWorkflowState($definition, false);
    }

    /**
     * @param ConfigInterface $entityConfig
     */
    private function persistEntityConfig(ConfigInterface $entityConfig)
    {
        $this->configManager->persist($entityConfig);
        $this->configManager->flush();
    }

    /**
     * @param $entityClass
     * @return ConfigInterface
     * @throws WorkflowException
     */
    private function getEntityConfig($entityClass)
    {
        $workflowConfigProvider = $this->configManager->getProvider(self::CONFIG_PROVIDER_NAME);
        if ($workflowConfigProvider->hasConfig($entityClass)) {
            return $workflowConfigProvider->getConfig($entityClass);
        }

        throw new WorkflowException(sprintf('Entity %s is not configurable', $entityClass));
    }

    /**
     * @param WorkflowDefinition $definition
     * @param bool $isActive
     * @return ConfigInterface
     */
    private function setWorkflowState(WorkflowDefinition $definition, $isActive)
    {
        $entityConfig = $this->getEntityConfig($definition->getRelatedEntity());

        $configValue = $entityConfig->get(self::CONFIG_KEY, false, []);
        $newConfigValue = $isActive
            ? array_merge($configValue, [$definition->getName()])
            : array_diff($configValue, [$definition->getName()]);
        $entityConfig->set(self::CONFIG_KEY, array_values($newConfigValue));

        $this->persistEntityConfig($entityConfig);
        $event = $isActive ? WorkflowEvents::WORKFLOW_ACTIVATED : WorkflowEvents::WORKFLOW_DEACTIVATED;

        $this->eventDispatcher->dispatch($event, new WorkflowChangesEvent($definition));
    }
}
