<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Model\Import\ProcessDefinitionsConfigurator;
use Oro\Bundle\WorkflowBundle\Model\Import\ProcessTriggersConfigurator;

class ProcessConfigurator
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var ProcessDefinitionsConfigurator
     */
    private $definitionImport;

    /**
     * @var ProcessTriggersConfigurator
     */
    private $triggersConfigurator;

    /**
     * @param ManagerRegistry $registry
     * @param ProcessDefinitionsConfigurator $definitionImport
     * @param ProcessTriggersConfigurator $triggersImport
     * @param string $definitionClass
     */
    public function __construct(
        ManagerRegistry $registry,
        ProcessDefinitionsConfigurator $definitionImport,
        ProcessTriggersConfigurator $triggersImport,
        $definitionClass
    ) {
        $this->registry = $registry;
        $this->definitionImport = $definitionImport;
        $this->triggersConfigurator = $triggersImport;
        $this->definitionClass = $definitionClass;
    }

    /**
     * @param array $processConfigurations
     */
    public function import(array $processConfigurations = [])
    {

        if (array_key_exists(ProcessConfigurationProvider::NODE_DEFINITIONS, $processConfigurations)) {
            $this->definitionImport->import(
                $processConfigurations[ProcessConfigurationProvider::NODE_DEFINITIONS]
            );
        }

        if (array_key_exists(ProcessConfigurationProvider::NODE_TRIGGERS, $processConfigurations)) {
            $this->triggersConfigurator->updateTriggers(
                $processConfigurations[ProcessConfigurationProvider::NODE_TRIGGERS],
                $this->getRepository()->findAll()
            );
        }

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
