<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\Import\ProcessDefinitionImport;
use Oro\Bundle\WorkflowBundle\Model\Import\ProcessTriggersImport;

class ProcessImport
{
    /**
     * @var ProcessTriggerScheduler
     */
    private $triggerScheduler;

    /**
     * @var array|ProcessDefinition[]
     */
    private $loadedDefinitions = [];

    /**
     * @var array|ProcessTrigger[]
     */
    private $loadedTriggers = [];

    /**
     * @var Schedule[]
     */
    private $createdSchedules = [];

    /**
     * @var ProcessDefinitionImport
     */
    private $definitionImport;

    /**
     * @var ProcessTriggersImport
     */
    private $triggersImport;

    /**
     * @param ProcessDefinitionImport $definitionImport
     * @param ProcessTriggersImport $triggersImport
     * @param ProcessTriggerScheduler $processCronScheduler
     */
    public function __construct(
        ProcessDefinitionImport $definitionImport,
        ProcessTriggersImport $triggersImport,
        ProcessTriggerScheduler $processCronScheduler
    ) {
        $this->definitionImport = $definitionImport;
        $this->triggersImport = $triggersImport;
        $this->triggerScheduler = $processCronScheduler;
    }

    /**
     * @param array|null $processConfigurations
     */
    public function import(array $processConfigurations = null)
    {
        $this->loadedDefinitions = $this->definitionImport->import(
            $processConfigurations[ProcessConfigurationProvider::NODE_DEFINITIONS]
        );

        $this->loadedTriggers = $this->triggersImport->import(
            $processConfigurations[ProcessConfigurationProvider::NODE_TRIGGERS],
            $this->definitionImport->getDefinitionsRepository()->findAll()
        );
        
        if (count($this->loadedTriggers) !== 0) {
            foreach ($this->triggersImport->getTriggersRepository()->findAllCronTriggers() as $cronTrigger) {
                $this->triggerScheduler->add($cronTrigger);
            }

            $this->createdSchedules = $this->triggerScheduler->flush();
        } else {
            $this->createdSchedules = [];
        }
    }

    /**
     * @return \Oro\Bundle\CronBundle\Entity\Schedule[]
     */
    public function getCreatedSchedules()
    {
        return $this->createdSchedules;
    }

    /**
     * @return array|\Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger[]
     */
    public function getLoadedTriggers()
    {
        return $this->loadedTriggers;
    }

    /**
     * @return array|\Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition[]
     */
    public function getLoadedDefinitions()
    {
        return $this->loadedDefinitions;
    }
}
