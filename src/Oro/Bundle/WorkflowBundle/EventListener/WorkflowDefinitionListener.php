<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ProcessConfigurationGenerator;
use Oro\Bundle\WorkflowBundle\Model\ProcessImport;

use Oro\Component\DependencyInjection\ServiceLink;

class WorkflowDefinitionListener
{
    /** @var ProcessConfigurationGenerator */
    protected $generator;

    /** @var ServiceLink */
    protected $importLink;

    /**
     * @param ProcessConfigurationGenerator $generator
     * @param ServiceLink $importLink
     */
    public function __construct(ProcessConfigurationGenerator $generator, ServiceLink $importLink)
    {
        $this->generator = $generator;
        $this->importLink = $importLink;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $this->onWorkflowDefinitionChange($args->getEntity());
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->onWorkflowDefinitionChange($args->getEntity());
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
     * @param object|WorkflowDefinition $entity
     */
    protected function onWorkflowDefinitionChange($entity)
    {
        if (!$this->isSupportedEntity($entity)) {
            return;
        }

        $configuration = $this->generator->generateForScheduledTransition($entity);

        $this->getProcessImport()->import($configuration);
    }

    /**
     * @return ProcessImport
     */
    protected function getProcessImport()
    {
        $service = $this->importLink->getService();

        if (!$service instanceof ProcessImport) {
            throw new \RuntimeException('Instance of Oro\Bundle\WorkflowBundle\Model\ProcessImport expected.');
        }

        return $service;
    }
}
