<?php

namespace Oro\Bundle\WorkflowBundle\Event;

use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Symfony\Contracts\EventDispatcher\Event;

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
