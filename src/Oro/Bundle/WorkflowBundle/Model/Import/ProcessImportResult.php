<?php

namespace Oro\Bundle\WorkflowBundle\Model\Import;

use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;


class ProcessImportResult
{
    /**
     * @var array|ProcessDefinition[]
     */
    private $definitions;

    /**
     * @var array|ProcessTrigger[]
     */
    private $triggers;

    /**
     * @var array|Schedule[]
     */
    private $schedules;

    /**
     * @return array|\Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition[]
     */
    public function getDefinitions()
    {
        return $this->definitions;
    }

    /**
     * @param array|\Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition[] $definitions
     * @return $this
     */
    public function setDefinitions(array $definitions)
    {
        $this->definitions = $definitions;

        return $this;
    }

    /**
     * @return array|\Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger[]
     */
    public function getTriggers()
    {
        return $this->triggers;
    }

    /**
     * @param array|\Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger[] $triggers
     * @return $this
     */
    public function setTriggers(array $triggers)
    {
        $this->triggers = $triggers;

        return $this;
    }

    /**
     * @return array|\Oro\Bundle\CronBundle\Entity\Schedule[]
     */
    public function getSchedules()
    {
        return $this->schedules;
    }

    /**
     * @param array|\Oro\Bundle\CronBundle\Entity\Schedule[] $schedules
     * @return $this
     */
    public function setSchedules(array $schedules)
    {
        $this->schedules = $schedules;

        return $this;
    }
}
