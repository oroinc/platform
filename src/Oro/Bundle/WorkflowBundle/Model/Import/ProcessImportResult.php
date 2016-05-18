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
     * @return array|ProcessDefinition[]
     */
    public function getDefinitions()
    {
        return $this->definitions;
    }

    /**
     * @param array|ProcessDefinition[] $definitions
     * @return $this
     */
    public function setDefinitions(array $definitions)
    {
        $this->definitions = $definitions;

        return $this;
    }

    /**
     * @return array|ProcessTrigger[]
     */
    public function getTriggers()
    {
        return $this->triggers;
    }

    /**
     * @param array|ProcessTrigger[] $triggers
     * @return $this
     */
    public function setTriggers(array $triggers)
    {
        $this->triggers = $triggers;

        return $this;
    }

    /**
     * @return array|Schedule[]
     */
    public function getSchedules()
    {
        return $this->schedules;
    }

    /**
     * @param array|Schedule[] $schedules
     * @return $this
     */
    public function setSchedules(array $schedules)
    {
        $this->schedules = $schedules;

        return $this;
    }
}
