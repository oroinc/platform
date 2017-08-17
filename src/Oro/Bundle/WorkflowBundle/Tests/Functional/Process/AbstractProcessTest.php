<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Process;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Model\ProcessHandler;

abstract class AbstractProcessTest extends WebTestCase
{
    /**
     * @return ProcessHandler
     */
    abstract protected function getProcessHandler();

    /**
     * @param ProcessDefinition $definition
     * @param ProcessData|null $data
     * @param string $event
     */
    protected function executeProcess(
        ProcessDefinition $definition,
        ProcessData $data = null,
        $event = ProcessTrigger::EVENT_CREATE
    ) {
        $trigger = new ProcessTrigger();
        $trigger->setId($definition->getName());
        $trigger->setDefinition($definition);
        $trigger->setEvent($event);

        $this->getProcessHandler()->handleTrigger($trigger, $data ?: new ProcessData());
    }
}
