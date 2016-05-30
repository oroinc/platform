<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Model\Import\ProcessDefinitionsImport;
use Oro\Bundle\WorkflowBundle\Model\Import\ProcessImportResult;
use Oro\Bundle\WorkflowBundle\Model\Import\ProcessTriggersImport;

class ProcessStorage
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
     * Removes all process definitions from database by their names
     * @param array|string $names
     */
    public function remove($names)
    {
        $objectManager = $this->getObjectManager();
        $repository = $this->getRepository();
        $dirty = false;
        foreach ((array)$names as $processDefinitionName) {
            $definition = $repository->find($processDefinitionName);
            if ($definition) {
                $objectManager->remove($definition);
                $dirty = true;
            }
        }
        if ($dirty) {
            $objectManager->flush();
        }
    }

    /**
     * @return ObjectRepository
     */
    protected function getRepository()
    {
        return $this->getObjectManager()->getRepository($this->definitionClass);
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager|null
     */
    protected function getObjectManager()
    {
        return $this->registry->getManagerForClass($this->definitionClass);
    }
}
