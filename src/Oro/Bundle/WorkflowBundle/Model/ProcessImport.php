<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Model\Import\ProcessDefinitionsImport;
use Oro\Bundle\WorkflowBundle\Model\Import\ProcessImportResult;
use Oro\Bundle\WorkflowBundle\Model\Import\ProcessTriggersImport;

class ProcessImport
{
    /**
     * @var ProcessDefinitionsImport
     */
    private $definitionImport;

    /**
     * @var ProcessTriggersImport
     */
    private $triggersImport;

    /**
     * @param ProcessDefinitionsImport $definitionImport
     * @param ProcessTriggersImport $triggersImport
     */
    public function __construct(
        ProcessDefinitionsImport $definitionImport,
        ProcessTriggersImport $triggersImport
    ) {
        $this->definitionImport = $definitionImport;
        $this->triggersImport = $triggersImport;
    }

    /**
     * @param array $processConfigurations
     * @return ProcessImportResult
     */
    public function import(array $processConfigurations = null)
    {
        $importResult = new ProcessImportResult();

        $importResult->setDefinitions(
            $this->definitionImport->import(
                $processConfigurations[ProcessConfigurationProvider::NODE_DEFINITIONS]
            )
        );

        $importResult->setTriggers(
            $this->triggersImport->import(
                $processConfigurations[ProcessConfigurationProvider::NODE_TRIGGERS],
                $this->definitionImport->getDefinitionsRepository()->findAll()
            )
        );
        
        $importResult->setSchedules($this->triggersImport->getCreatedSchedules());

        return $importResult;
    }
}
