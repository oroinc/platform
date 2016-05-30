<?php

namespace Oro\Bundle\WorkflowBundle\Model\Import;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository;
use Oro\Bundle\WorkflowBundle\Model\ProcessCronScheduler;

class ProcessTriggersImport
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
    private $triggerEntityClass;

    /**
     * @var ProcessCronScheduler
     */
    private $processCronScheduler;

    /**
     * @var array|Schedule[]
     */
    private $createdSchedules = [];

    /**
     * @param ProcessConfigurationBuilder $configurationBuilder
     * @param ManagerRegistry $registry
     * @param string $triggerEntityClass
     * @param ProcessCronScheduler $processCronScheduler
     */
    public function __construct(
        ProcessConfigurationBuilder $configurationBuilder,
        ManagerRegistry $registry,
        $triggerEntityClass,
        ProcessCronScheduler $processCronScheduler
    ) {
        $this->configurationBuilder = $configurationBuilder;
        $this->registry = $registry;
        $this->triggerEntityClass = $triggerEntityClass;
        $this->processCronScheduler = $processCronScheduler;
    }

    /**
     * @param array $triggersConfiguration
     * @param array|ProcessDefinition[] $definitions
     * @return ProcessTrigger[]
     */
    public function import(array $triggersConfiguration, array $definitions)
    {
        $importedTriggers = [];

        $namedDefinitions = [];
        foreach ($definitions as $definition) {
            $namedDefinitions[$definition->getName()] = $definition;
        }

        $triggers = $this->configurationBuilder->buildProcessTriggers(
            $triggersConfiguration,
            $namedDefinitions
        );

        /** @var ProcessTriggerRepository $triggerRepository */
        $triggerRepository = $this->getRepository();

        if ($triggers) { #because of flush
            $entityManager = $this->getObjectManager();

            foreach ($triggers as $trigger) {
                $existingTrigger = $triggerRepository->findEqualTrigger($trigger);
                if ($existingTrigger) {
                    $existingTrigger->import($trigger);
                    $importedTriggers[] = $existingTrigger;
                } else {
                    $entityManager->persist($trigger);
                    $importedTriggers[] = $trigger;
                }
            }

            $entityManager->flush();
        }

        $this->createdSchedules = [];

        if (count($importedTriggers) > 0) {
            foreach ($triggerRepository->findAllCronTriggers() as $cronTrigger) {
                $this->processCronScheduler->add($cronTrigger);
            }
            $this->createdSchedules = $this->processCronScheduler->flush();
        }

        return $importedTriggers;
    }

    /**
     * @return array|Schedule[]
     */
    public function getCreatedSchedules()
    {
        return $this->createdSchedules;
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->registry->getManagerForClass($this->triggerEntityClass);
    }

    /**
     * @return ObjectRepository
     */
    protected function getRepository()
    {
        return $this->getObjectManager()->getRepository($this->triggerEntityClass);
    }
}
