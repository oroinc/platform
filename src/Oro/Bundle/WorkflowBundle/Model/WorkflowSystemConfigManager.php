<?php
/**
 * Created by PhpStorm.
 * User: Matey
 * Date: 17.06.2016
 * Time: 14:36
 */

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WorkflowSystemConfigManager
{
    const CONFIG_PROVIDER_NAME = 'workflow';
    const CONFIG_KEY = 'active_workflows';

    /** @var ConfigManager */
    private $configManager;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    public function __construct(ConfigManager $configManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->configManager = $configManager;
        $this->eventDispatcher = $eventDispatcher;
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
     * todo fix duplicate code with setWorkflowInactive
     * @param WorkflowDefinition $definition
     * @throws WorkflowException
     */
    public function setWorkflowActive(WorkflowDefinition $definition)
    {
        $entityConfig = $this->getEntityConfig($definition->getRelatedEntity());

        $entityConfig->set(
            self::CONFIG_KEY,
            array_values(array_merge($entityConfig->get(self::CONFIG_KEY, false, []), [$definition->getName()]))
        );

        $this->persistEntityConfig($entityConfig);

        $this->eventDispatcher->dispatch(WorkflowEvents::WORKFLOW_ACTIVATED, new WorkflowChangesEvent($definition));
    }

    /**
     * todo fix duplicate code with setWorkflowActive
     * @param WorkflowDefinition $definition
     * @throws WorkflowException
     */
    public function setWorkflowInactive(WorkflowDefinition $definition)
    {
        $entityConfig = $this->getEntityConfig($definition->getRelatedEntity());
        
        $entityConfig->set(
            self::CONFIG_KEY,
            array_values(array_diff($entityConfig->get(self::CONFIG_KEY, false, []), [$definition->getName()]))
        );
        
        $this->persistEntityConfig($entityConfig);

        $this->eventDispatcher->dispatch(WorkflowEvents::WORKFLOW_DEACTIVATED, new WorkflowChangesEvent($definition));
    }

    /**
     * @param ConfigInterface $entityConfig
     */
    protected function persistEntityConfig(ConfigInterface $entityConfig)
    {
        $this->configManager->persist($entityConfig);
        $this->configManager->flush();
    }

    /**
     * @param $entityClass
     * @return ConfigInterface
     * @throws WorkflowException
     */
    protected function getEntityConfig($entityClass)
    {
        $workflowConfigProvider = $this->configManager->getProvider(self::CONFIG_PROVIDER_NAME);
        if ($workflowConfigProvider->hasConfig($entityClass)) {
            return $workflowConfigProvider->getConfig($entityClass);
        }

        throw new WorkflowException(sprintf('Entity %s is not configurable', $entityClass));
    }
}