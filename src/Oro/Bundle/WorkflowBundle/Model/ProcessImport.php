<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository;

class ProcessImport
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var ProcessConfigurationBuilder
     */
    private $configurationBuilder;

    /**
     * @var EntityRepository
     */
    private $definitionsRepository;

    /**
     * @var ProcessTriggerRepository
     */
    private $triggerRepository;

    /**
     * @var ProcessCronScheduler
     */
    private $processCronScheduler;

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
     * @param DoctrineHelper $doctrineHelper
     * @param ProcessConfigurationBuilder $configurationBuilder
     * @param ProcessCronScheduler $processCronScheduler
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ProcessConfigurationBuilder $configurationBuilder,
        ProcessCronScheduler $processCronScheduler
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configurationBuilder = $configurationBuilder;
        $this->processCronScheduler = $processCronScheduler;
    }

    /**
     * @param array|null $processConfigurations
     */
    public function import(array $processConfigurations = null)
    {
        $this->loadedTriggers = $this->loadedDefinitions = $this->createdSchedules = [];

        $this->processDefinitions($processConfigurations[ProcessConfigurationProvider::NODE_DEFINITIONS]);

        $this->processTriggers($processConfigurations[ProcessConfigurationProvider::NODE_TRIGGERS]);

        if (count($this->loadedTriggers) !== 0) {
            $this->processSchedules();
        }
    }

    /**
     * @param array $definitionsConfiguration
     */
    protected function processDefinitions(array $definitionsConfiguration)
    {
        $entityManager = $this->doctrineHelper->getEntityManager('OroWorkflowBundle:ProcessDefinition');
        $definitionRepository = $this->getDefinitionRepository();

        $definitions = $this->configurationBuilder->buildProcessDefinitions($definitionsConfiguration);

        if ($definitions) { #because of flush
            foreach ($definitions as $definition) {

                $definitionName = $definition->getName();
                /** @var ProcessDefinition $existingDefinition */
                $existingDefinition = $definitionRepository->find($definitionName);

                // definition should be overridden if definition with such name already exists
                if ($existingDefinition) {
                    $existingDefinition->import($definition);
                } else {
                    $entityManager->persist($definition);
                }

                $this->loadedDefinitions[$definitionName] = $definition;
            }

            $entityManager->flush();
        }
    }

    /**
     * @param array $triggersConfiguration
     */
    private function processTriggers(array $triggersConfiguration)
    {
        $definitionsByName = [];

        foreach ($this->getDefinitionRepository()->findAll() as $definition) {
            /**@var ProcessDefinition $definition */
            $definitionsByName[$definition->getName()] = $definition;
        }

        $triggers = $this->configurationBuilder->buildProcessTriggers($triggersConfiguration, $definitionsByName);

        if ($triggers) { #because of flush
            $entityManager = $this->doctrineHelper->getEntityManagerForClass('OroWorkflowBundle:ProcessTrigger');
            $triggerRepository = $this->getTriggerRepository();
            foreach ($triggers as $trigger) {
                $existingTrigger = $triggerRepository->findEqualTrigger($trigger);
                if ($existingTrigger) {
                    $existingTrigger->import($trigger);
                } else {
                    $entityManager->persist($trigger);
                }

                $this->loadedTriggers[] = $trigger;
            }

            $entityManager->flush();
        }
    }

    private function processSchedules()
    {
        foreach ($this->getTriggerRepository()->findAllCronTriggers() as $cronTrigger) {
            $this->processCronScheduler->add($cronTrigger);
        }

        $this->createdSchedules = $this->processCronScheduler->flush();
    }

    /**
     * @return EntityRepository|ProcessTriggerRepository
     */
    private function getTriggerRepository()
    {
        if (null !== $this->triggerRepository) {
            return $this->triggerRepository;
        }

        return $this->triggerRepository = $this->doctrineHelper
            ->getEntityRepositoryForClass('OroWorkflowBundle:ProcessTrigger');
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    private function getDefinitionRepository()
    {
        if (null !== $this->definitionsRepository) {
            return $this->definitionsRepository;
        }

        return $this->definitionsRepository = $this->doctrineHelper->getEntityRepositoryForClass(
            'OroWorkflowBundle:ProcessDefinition'
        );
    }

    /**
     * @return \Oro\Bundle\CronBundle\Entity\Schedule[]
     */
    public function getCreatedSchedules()
    {
        return $this->createdSchedules;
    }
}
