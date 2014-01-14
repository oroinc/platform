<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\ProcessFactory;

class ProcessHandler
{
    /**
     * @var ProcessFactory
     */
    protected $processFactory;

    /**
     * @param ProcessFactory $processFactory
     */
    public function __construct(ProcessFactory $processFactory)
    {
        $this->processFactory = $processFactory;
    }

    /**
     * @param ProcessTrigger $processTrigger
     * @param object $entity
     * @param null|mixed $old
     * @param null|mixed $new
     */
    public function handleTrigger(ProcessTrigger $processTrigger, $entity, $old = null, $new = null)
    {
        $contextData = array('entity' => $entity);
        if ($old || $new) {
            $contextData['old'] = $old;
            $contextData['new'] = $new;
        }
        $process = $this->processFactory->create($processTrigger->getDefinition());
        $process->execute(new ArrayCollection($contextData));
    }

    /**
     * @param ProcessJob $processJob
     */
    public function handleJob(ProcessJob $processJob)
    {
        $processTrigger = $processJob->getProcessTrigger();
        throw new \Exception('Blocked by CRM-763');

        //TODO: add logic for old/new on field update and get serialized entity for delete
        $processJobData = $processJob->getData();
        $this->handleTrigger($processTrigger, $entity, $old, $new);
    }
}
