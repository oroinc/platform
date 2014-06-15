<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;

class ProcessHandler
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ProcessFactory
     */
    protected $processFactory;

    /**
     * @param ProcessFactory $processFactory
     * @param EntityManager $entityManager
     */
    public function __construct(ProcessFactory $processFactory, EntityManager $entityManager)
    {
        $this->entityManager  = $entityManager;
        $this->processFactory = $processFactory;
    }

    /**
     * @param ProcessTrigger $processTrigger
     * @param object $entity
     * @param null|mixed $old
     * @param null|mixed $new
     * @throws InvalidParameterException
     */
    public function handleTrigger(ProcessTrigger $processTrigger, $entity, $old = null, $new = null)
    {
        if (!$entity) {
            throw new InvalidParameterException(
                sprintf('Invalid data for the "%s" function. Entity parameter can not be empty.', __FUNCTION__)
            );
        }

        $contextData = array('entity' => $entity);
        if ($old || $new) {
            $contextData['old'] = $old;
            $contextData['new'] = $new;
        }
        $process = $this->processFactory->create($processTrigger->getDefinition());
        $process->execute(new ProcessData($contextData));
    }

    public function handleJob(ProcessJob $processJob)
    {
        $processTrigger = $processJob->getProcessTrigger();
        list($entity, $old, $new) = $this->getDataForHandleTrigger($processJob, $processTrigger->getEvent());
        $this->handleTrigger($processTrigger, $entity, $old, $new);
    }

    /**
     * @param ProcessJob $processJob
     * @param string $triggerEvent
     * @return array
     * @throws InvalidParameterException
     */
    protected function getDataForHandleTrigger(ProcessJob $processJob, $triggerEvent)
    {
        $old = $new = null;
        $processJobData = $processJob->getData();

        switch ($triggerEvent) {
            case ProcessTrigger::EVENT_DELETE:
                if (empty($processJobData['entity'])) {
                    throw new InvalidParameterException(
                        'Invalid process job data for the delete event. Entity can not be empty.'
                    );
                } elseif (!is_object($processJobData['entity'])) {
                    throw new InvalidParameterException(
                        'Invalid process job data for the delete event. Entity must be an object.'
                    );
                }

                return array($processJobData['entity'], null, null);
            case ProcessTrigger::EVENT_UPDATE:
                $old = $processJobData['old'];
                $new = $processJobData['new'];
            // break intentionally omitted
            case ProcessTrigger::EVENT_CREATE:
                $repository = $this->entityManager->getRepository('OroWorkflowBundle:ProcessJob');
                return array($repository->findEntity($processJob), $old, $new);
            default:
                throw new InvalidParameterException(sprintf('Got invalid or unregister event "%s"', $triggerEvent));
        }
    }
}
