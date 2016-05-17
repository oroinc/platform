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
     * @var array|ProcessDefinition[]
     */
    private $loadedDefinitions = [];

    /**
     * @var array|ProcessTrigger[]
     */
    private $loadedTriggers = [];

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
     */
    public function __construct(
        ProcessDefinitionImport $definitionImport,
        ProcessTriggersImport $triggersImport
    ) {
        $this->definitionImport = $definitionImport;
        $this->triggersImport = $triggersImport;
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
    }

    /**
     * @return array|Schedule[]
     */
    public function getCreatedSchedules()
    {
        return $this->triggersImport->getCreatedSchedules();
    }

    /**
     * @return array|ProcessTrigger[]
     */
    public function getLoadedTriggers()
    {
        return $this->loadedTriggers;
    }

    /**
     * @return array|ProcessDefinition[]
     */
    public function getLoadedDefinitions()
    {
        return $this->loadedDefinitions;
    }
}
