<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Model\Import\ProcessDefinitionsImport;
use Oro\Bundle\WorkflowBundle\Model\Import\ProcessImportResult;
use Oro\Bundle\WorkflowBundle\Model\Import\ProcessTriggersImport;

class ProcessImport
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var ProcessDefinitionsImport
     */
    private $definitionImport;

    /**
     * @var ProcessTriggersImport
     */
    private $triggersImport;

    /**
     * @param ManagerRegistry $registry
     * @param ProcessDefinitionsImport $definitionImport
     * @param ProcessTriggersImport $triggersImport
     * @param string $definitionClass
     */
    public function __construct(
        ManagerRegistry $registry,
        ProcessDefinitionsImport $definitionImport,
        ProcessTriggersImport $triggersImport,
        $definitionClass
    ) {
        $this->registry = $registry;
        $this->definitionImport = $definitionImport;
        $this->triggersImport = $triggersImport;
        $this->definitionClass = $definitionClass;
    }

    /**
     * @param array $processConfigurations
     * @return ProcessImportResult
     */
    public function import(array $processConfigurations = [])
    {
        $importResult = new ProcessImportResult();

        if (array_key_exists(ProcessConfigurationProvider::NODE_DEFINITIONS, $processConfigurations)) {
            $importResult->setDefinitions(
                $this->definitionImport->import(
                    $processConfigurations[ProcessConfigurationProvider::NODE_DEFINITIONS]
                )
            );
        }

        if (array_key_exists(ProcessConfigurationProvider::NODE_TRIGGERS, $processConfigurations)) {
            $importResult->setTriggers(
                $this->triggersImport->import(
                    $processConfigurations[ProcessConfigurationProvider::NODE_TRIGGERS],
                    $this->getRepository()->findAll()
                )
            );
        }

        $importResult->setSchedules($this->triggersImport->getCreatedSchedules());

        return $importResult;
    }

    /**
     * @return ObjectRepository
     */
    protected function getRepository()
    {
        return $this->registry->getManagerForClass($this->definitionClass)->getRepository($this->definitionClass);
    }
}
