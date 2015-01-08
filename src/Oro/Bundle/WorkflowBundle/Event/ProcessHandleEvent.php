<?php

namespace Oro\Bundle\WorkflowBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;

class ProcessHandleEvent extends Event
{
    /**
     * @var ProcessTrigger
     */
    protected $processTrigger;

    /**
     * @var ProcessData
     */
    protected $processData;

    /**
     * @param ProcessTrigger $processTrigger
     * @param ProcessData $processData
     */
    public function __construct(ProcessTrigger $processTrigger, ProcessData $processData)
    {
        $this->processTrigger = $processTrigger;
        $this->processData = $processData;
    }

    /**
     * @return ProcessTrigger
     */
    public function getProcessTrigger()
    {
        return $this->processTrigger;
    }

    /**
     * @return ProcessData
     */
    public function getProcessData()
    {
        return $this->processData;
    }
}
