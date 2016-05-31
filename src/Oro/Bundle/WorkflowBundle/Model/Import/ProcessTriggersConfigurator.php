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
use Oro\Bundle\WorkflowBundle\Model\ProcessTriggerCronScheduler;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class ProcessTriggersConfigurator implements LoggerAwareInterface
{
    use LoggerAwareTrait;
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
     * @var ProcessTriggerCronScheduler
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
     * @param ProcessTriggerCronScheduler $processCronScheduler
     */
    public function __construct(
        ProcessConfigurationBuilder $configurationBuilder,
        ManagerRegistry $registry,
        $triggerEntityClass,
        ProcessTriggerCronScheduler $processCronScheduler
    ) {
        $this->configurationBuilder = $configurationBuilder;
        $this->registry = $registry;
        $this->triggerEntityClass = $triggerEntityClass;
        $this->processCronScheduler = $processCronScheduler;
        $this->setLogger(new NullLogger());
    }

    /**
     * Update ProcessTriggers by corresponding $triggersConfiguration.
     * If $triggersConfiguration contains empty array under definition name key - all process triggers will be removed.
     * If $triggersConfiguration has key not presented by any in $definitions - \LogicException will be thrown
     * If definition name from $definitions will NOT be present in $triggersConfiguration keys - nothing happens
     * @param array $triggersConfiguration
     * @param array|ProcessDefinition[] $definitions array of definitions for triggers
     * @return ProcessTrigger[]
     */
    public function updateTriggers(array $triggersConfiguration, array $definitions)
    {
        //$created = [];
        //$updated = [];
        //$removed = [];
        /** @var ProcessTriggerRepository $triggerRepository */
        $triggerRepository = $this->getRepository();
        $entityManager = $this->getObjectManager();

        foreach ($definitions as $definition) {
            $definitionName = $definition->getName();
            $storedTriggers = $triggerRepository->findByDefinition($definition);

            if (array_key_exists($definitionName, $triggersConfiguration)) {
                foreach ($triggersConfiguration[$definitionName] as $triggerConfiguration) {
                    $builtTrigger = $this->configurationBuilder->buildProcessTrigger(
                        $triggerConfiguration,
                        $definition
                    );

                    $existingTrigger = $this->pickExistentTrigger($storedTriggers, $builtTrigger);
                    if ($existingTrigger) {
                        $existingTrigger->import($builtTrigger);
                        //$updated[] = $existingTrigger;
                    } else {
                        $entityManager->persist($builtTrigger);
                        //$created[] = $builtTrigger;
                    }
                    $this->addScheduled($builtTrigger);
                }

                foreach ($storedTriggers as $triggerForRemove) {
                    $entityManager->remove($triggerForRemove);
                    $this->processCronScheduler->remove($triggerForRemove);
                }

                //$removed[] = $storedTriggers;
                unset($triggersConfiguration[$definitionName]);
            }
        }

        if (count($triggersConfiguration) !== 0) { //unprocessed triggers configurations - no definitions comes
            throw new \LogicException(
                sprintf(
                    'Process definitions "%s" not provided for triggers configurations',
                    implode('", "', array_keys($triggersConfiguration))
                )
            );
        }
    }

    /**
     * @param ProcessTrigger $trigger
     */
    protected function removeScheduled(ProcessTrigger $trigger)
    {
        if ($trigger->getCron()) {
            $this->processCronScheduler->remove($trigger);
        }
    }

    /**
     * @param ProcessTrigger $trigger
     */
    protected function addScheduled(ProcessTrigger $trigger)
    {
        if ($trigger->getCron()) {
            $this->processCronScheduler->add($trigger);
        }
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

    /**
     * @param array|ProcessTrigger[] $storedTriggers
     * @param ProcessTrigger $builtTrigger
     * @return ProcessTrigger|null
     */
    private function pickExistentTrigger(array &$storedTriggers, ProcessTrigger $builtTrigger)
    {
        foreach ($storedTriggers as $k => $trigger) {
            if ($trigger->isDefinitiveEqual($builtTrigger)) {
                unset($storedTriggers[$k]);

                return $trigger;
            }
        }

        return null;
    }
}
