<?php

namespace Oro\Bundle\WorkflowBundle\Model\Import;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository;
use Oro\Bundle\WorkflowBundle\Model\ProcessTriggerScheduler;
use Oro\Component\DoctrineUtils\ORM\EntityManagementTrait;

class ProcessTriggersImport
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
    private $triggerEntityClass;

    /**
     * @var ProcessTriggerScheduler
     */
    private $processCronScheduler;

    /**
     * @var array|Schedule[]
     */
    private $createdSchedules;

    /**
     * @param ProcessConfigurationBuilder $configurationBuilder
     * @param ManagerRegistry $managerRegistry
     * @param string $triggerEntityClass
     * @param ProcessTriggerScheduler $processCronScheduler
     */
    public function __construct(
        ProcessConfigurationBuilder $configurationBuilder,
        ManagerRegistry $managerRegistry,
        $triggerEntityClass,
        ProcessTriggerScheduler $processCronScheduler
    ) {
        $this->configurationBuilder = $configurationBuilder;
        $this->managerRegistry = $managerRegistry;
        $this->triggerEntityClass = $triggerEntityClass;
        $this->processCronScheduler = $processCronScheduler;
    }

    /**
     * @param array $triggersConfiguration
     * @param array|ProcessDefinition[] $definitions
     * @return \Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger[]
     */
    public function import(array $triggersConfiguration, array $definitions)
    {
        $loadedTriggers = [];

        $triggers = $this->configurationBuilder->buildProcessTriggers(
            $triggersConfiguration,
            $this->namedDefinitionsArray($definitions)
        );
        
        /** @var ProcessTriggerRepository $triggerRepository */
        $triggerRepository = $this->getRepository();

        if ($triggers) { #because of flush
            $entityManager = $this->getObjectManager();
            
            foreach ($triggers as $trigger) {
                if ($existingTrigger = $triggerRepository->findEqualTrigger($trigger)) {
                    $existingTrigger->import($trigger);
                } else {
                    $entityManager->persist($trigger);
                }

                $loadedTriggers[] = $trigger;
            }

            $entityManager->flush();
        }
        
        $this->createdSchedules = [];

        if (count($loadedTriggers) > 0) {
            foreach ($triggerRepository->findAllCronTriggers() as $cronTrigger) {
                $this->processCronScheduler->add($cronTrigger);
            }
            $this->createdSchedules = $this->processCronScheduler->flush();
        }

        return $loadedTriggers;
    }

    /**
     * @param array|ProcessDefinition[] $definitions
     * @return array|ProcessDefinition[]
     */
    private function namedDefinitionsArray(array $definitions)
    {
        $namedDefinitions = [];
        foreach ($definitions as $definition) {
            $namedDefinitions[$definition->getName()] = $definition;
        }

        return $namedDefinitions;
    }

    /**
     * @return array|Schedule[]
     */
    public function getCreatedSchedules()
    {
        return $this->createdSchedules;
    }

    /**
     * @return string
     */
    protected function getEntityClass()
    {
        return $this->triggerEntityClass;
    }

    /**
     * @return ManagerRegistry
     */
    protected function getManagerRegistry()
    {
        return $this->managerRegistry;
    }
}
