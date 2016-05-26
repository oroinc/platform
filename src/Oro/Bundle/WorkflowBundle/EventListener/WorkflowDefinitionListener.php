<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ProcessConfigurationGenerator;
use Oro\Bundle\WorkflowBundle\Model\ProcessImport;

class WorkflowDefinitionListener
{
    /** @var ProcessConfigurationGenerator */
    protected $generator;

    /** @var ProcessImport */
    protected $import;

    /**
     * @param ProcessConfigurationGenerator $generator
     * @param ProcessImport $import
     */
    public function __construct(ProcessConfigurationGenerator $generator, ProcessImport $import)
    {
        $this->generator = $generator;
        $this->import = $import; //todo wrap import to sync
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

        $this->import->import($configuration);
    }
}
