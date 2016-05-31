<?php

namespace Oro\Bundle\WorkflowBundle\Model\Import;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;

class ProcessDefinitionsConfigurator
{
    /**
     * @var ProcessConfigurationBuilder
     */
    private $configurationBuilder;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var string
     */
    private $definitionClass;

    /**
     * @param ProcessConfigurationBuilder $configurationBuilder
     * @param ManagerRegistry $registry
     * @param string $definitionClass
     */
    public function __construct(
        ProcessConfigurationBuilder $configurationBuilder,
        ManagerRegistry $registry,
        $definitionClass
    ) {
        $this->configurationBuilder = $configurationBuilder;
        $this->registry = $registry;
        $this->definitionClass = $definitionClass;
    }

    /**
     * @param array $definitionsConfiguration
     * @return ProcessDefinition[]
     */
    public function import(array $definitionsConfiguration)
    {
        $importedDefinitions = [];

        $entityManager = $this->getObjectManager();
        $definitionRepository = $this->getRepository();

        $definitions = $this->configurationBuilder->buildProcessDefinitions($definitionsConfiguration);

        if ($definitions) { #because of flush
            foreach ($definitions as $definition) {
                $definitionName = $definition->getName();
                /** @var ProcessDefinition $existingDefinition */
                // definition should be overridden if definition with such name already exists
                $existingDefinition = $definitionRepository->find($definitionName);
                if ($existingDefinition) {
                    $existingDefinition->import($definition);
                    $importedDefinitions[$definitionName] = $existingDefinition;
                } else {
                    $entityManager->persist($definition);
                    $importedDefinitions[$definitionName] = $definition;
                }
            }

            $entityManager->flush();
        }

        return $importedDefinitions;
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->registry->getManagerForClass($this->definitionClass);
    }

    /**
     * @return ObjectRepository
     */
    protected function getRepository()
    {
        return $this->getObjectManager()->getRepository($this->definitionClass);
    }
}
