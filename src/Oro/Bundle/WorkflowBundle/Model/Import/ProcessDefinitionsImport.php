<?php

namespace Oro\Bundle\WorkflowBundle\Model\Import;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Component\DoctrineUtils\ORM\EntityManagementTrait;

class ProcessDefinitionsImport
{
    use EntityManagementTrait;

    /**
     * @var ProcessConfigurationBuilder
     */
    private $configurationBuilder;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var string
     */
    private $definitionClass;

    /**
     * @param ProcessConfigurationBuilder $configurationBuilder
     * @param ManagerRegistry $managerRegistry
     * @param string $definitionClass
     */
    public function __construct(
        ProcessConfigurationBuilder $configurationBuilder,
        ManagerRegistry $managerRegistry,
        $definitionClass
    ) {
        $this->configurationBuilder = $configurationBuilder;
        $this->managerRegistry = $managerRegistry;
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
                } else {
                    $entityManager->persist($definition);
                }

                $importedDefinitions[$definitionName] = $definition;
            }

            $entityManager->flush();
        }

        return $importedDefinitions;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getDefinitionsRepository()
    {
        return $this->getRepository();
    }

    /**
     * @return string
     */
    protected function getEntityClass()
    {
        return $this->definitionClass;
    }

    /**
     * @return ManagerRegistry
     */
    protected function getManagerRegistry()
    {
        return $this->managerRegistry;
    }
}
