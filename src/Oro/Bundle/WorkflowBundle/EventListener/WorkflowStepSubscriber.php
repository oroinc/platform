<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Model\EntityConnector;

class WorkflowStepSubscriber implements EventSubscriber
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var EntityConnector
     */
    protected $entityConnector;

    /**
     * @var WorkflowDefinition[]
     */
    protected $definitionsWithDefaultStep;

    /**
     * @param ManagerRegistry $registry
     * @param EntityConnector $entityConnector
     */
    public function __construct(ManagerRegistry $registry, EntityConnector $entityConnector)
    {
        $this->registry = $registry;
        $this->entityConnector = $entityConnector;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            // @codingStandardsIgnoreStart
            Events::prePersist
            // @codingStandardsIgnoreEnd
        );
    }

    /**
     * Set start step for entities with workflow
     *
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($this->isSupportStartStep($entity)) {
            $this->setStartStep($entity);
        }
    }

    /**
     * @param object $entity
     * @return boolean
     */
    protected function isSupportStartStep($entity)
    {
        if (null === $this->definitionsWithDefaultStep) {
            /** @var WorkflowDefinitionRepository $repository */
            $repository = $this->registry->getRepository('OroWorkflowBundle:WorkflowDefinition');
            $definitions = $repository->findAllWithStartStep();

            $this->definitionsWithDefaultStep = array();
            foreach ($definitions as $definition) {
                $this->definitionsWithDefaultStep[$definition->getRelatedEntity()] = $definition;
            }
        }

        return array_key_exists(ClassUtils::getClass($entity), $this->definitionsWithDefaultStep);
    }

    /**
     * @param object $entity
     */
    protected function setStartStep($entity)
    {
        $definition = $this->definitionsWithDefaultStep[ClassUtils::getClass($entity)];
        $this->entityConnector->setWorkflowStep($entity, $definition->getStartStep());
    }
}
