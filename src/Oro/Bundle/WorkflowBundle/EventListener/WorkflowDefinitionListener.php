<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\ProcessConfigurator;
use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ProcessConfigurationGenerator;
use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ScheduledTransitionProcesses;

use Oro\Component\DependencyInjection\ServiceLink;

/**
 * Provide logic for workflow scheduled transitions processes synchronization
 */
class WorkflowDefinitionListener
{
    /** @var ProcessConfigurationGenerator */
    protected $generator;

    /** @var ServiceLink */
    protected $processConfiguratorLink;

    /** @var ScheduledTransitionProcesses */
    private $scheduledTransitionProcessesLink;

    /**
     * @param ProcessConfigurationGenerator $generator
     * @param ServiceLink $processConfiguratorServiceLink
     * @param serviceLink $scheduledTransitionProcessesLink
     */
    public function __construct(
        ProcessConfigurationGenerator $generator,
        ServiceLink $processConfiguratorServiceLink,
        ServiceLink $scheduledTransitionProcessesLink
    ) {
        $this->generator = $generator;
        $this->processConfiguratorLink = $processConfiguratorServiceLink;
        $this->scheduledTransitionProcessesLink = $scheduledTransitionProcessesLink;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if (!$entity instanceof WorkflowDefinition) {
            return;
        }

        $configuration = $this->generator->generateForScheduledTransition($entity);

        $this->getProcessConfigurator()->import($configuration);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if (!$entity instanceof WorkflowDefinition) {
            return;
        }

        $generatedConfigurations = $this->generator->generateForScheduledTransition($entity);

        $processConfigurator = $this->getProcessConfigurator();
        $processConfigurator->import($generatedConfigurations);
        
        $persistedProcessDefinitions = $this->getScheduledTransitionProcesses()->workflowRelated($entity->getName());

        $toDelete = [];
        foreach ($persistedProcessDefinitions as $definition) {
            $name = $definition->getName();
            if (!array_key_exists($name, $generatedConfigurations[ProcessConfigurationProvider::NODE_DEFINITIONS])) {
                $toDelete[] = $definition->getName();
            }
        }

        $processConfigurator->remove($toDelete);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if (!$this->isSupportedEntity($entity)) {
            return;
        }

        $workflowScheduledProcesses = $this->getScheduledTransitionProcesses()->workflowRelated($entity->getName());

        $toDelete = [];
        foreach ($workflowScheduledProcesses as $definition) {
            $toDelete[] = $definition->getName();
        }

        $this->getProcessConfigurator()->remove($toDelete);
    }

    /**
     * @param object $entity
     * @return bool
     */
    protected function isSupportedEntity($entity)
    {
        return $entity instanceof WorkflowDefinition;
    }

    /**
     * @return ProcessConfigurator
     */
    protected function getProcessConfigurator()
    {
        $service = $this->processConfiguratorLink->getService();

        if (!$service instanceof ProcessConfigurator) {
            throw new \RuntimeException('Instance of Oro\Bundle\WorkflowBundle\Model\ProcessConfigurator expected.');
        }

        return $service;
    }

    /**
     * @return ScheduledTransitionProcesses
     */
    protected function getScheduledTransitionProcesses()
    {
        $service = $this->scheduledTransitionProcessesLink->getService();

        if (!$service instanceof ScheduledTransitionProcesses) {
            throw new \RuntimeException(
                'Instance of Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ScheduledTransitionProcesses expected.'
            );
        }

        return $service;
    }
}
