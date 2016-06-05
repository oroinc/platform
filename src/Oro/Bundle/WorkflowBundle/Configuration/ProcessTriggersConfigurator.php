<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository;
use Oro\Bundle\WorkflowBundle\Model\ProcessTriggerCronScheduler;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ProcessTriggersConfigurator implements LoggerAwareInterface
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var bool */
    protected $dirty;

    /** @var ProcessConfigurationBuilder */
    private $configurationBuilder;

    /** @var ManagerRegistry */
    private $registry;

    /** @var string */
    private $triggerEntityClass;

    /** @var ProcessTriggerCronScheduler */
    private $processCronScheduler;

    /** @var ProcessTrigger[] */
    private $triggers;

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
        $this->triggers = [];
        $this->configurationBuilder = $configurationBuilder;
        $this->registry = $registry;
        $this->triggerEntityClass = $triggerEntityClass;
        $this->processCronScheduler = $processCronScheduler;
        $this->setLogger(new NullLogger());
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->processCronScheduler->setLogger($logger);
    }

    /**
     * Update ProcessTriggers by corresponding $triggersConfiguration.
     * If $triggersConfiguration contains empty array under definition name key - all process triggers will be removed.
     * If $triggersConfiguration has key not presented by any in $definitions - \LogicException will be thrown
     * If definition name from $definitions will NOT be present in $triggersConfiguration keys - nothing happens
     *
     * @param array $triggersConfiguration
     * @param array|ProcessDefinition[] $definitions array of definitions for triggers
     *
     * @return ProcessTrigger[]
     * @throws \LogicException
     */
    public function configureTriggers(array $triggersConfiguration, array $definitions)
    {
        /** @var ProcessTriggerRepository $triggerRepository */
        $triggerRepository = $this->getRepository();
        $this->triggers = [];

        foreach ($definitions as $definition) {
            $definitionName = $definition->getName();
            $storedTriggers = $triggerRepository->findByDefinition($definition);

            if (array_key_exists($definitionName, $triggersConfiguration)) {
                foreach ($triggersConfiguration[$definitionName] as $triggerConfiguration) {
                    $newTrigger = $this->configurationBuilder->buildProcessTrigger(
                        $triggerConfiguration,
                        $definition
                    );

                    $existingTrigger = $this->pickExistentTrigger($storedTriggers, $newTrigger);
                    if ($existingTrigger) {
                        $this->update($existingTrigger, $newTrigger);
                        $newTrigger = $existingTrigger;
                    } else {
                        $this->create($newTrigger);
                    }
                    $this->triggers[] = $newTrigger;
                }

                foreach ($storedTriggers as $triggerForRemove) {
                    $this->delete($triggerForRemove);
                    $this->dropSchedule($triggerForRemove);
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
     * @return ProcessTriggerRepository
     */
    protected function getRepository()
    {
        return $this->getObjectManager()->getRepository($this->triggerEntityClass);
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->registry->getManagerForClass($this->triggerEntityClass);
    }

    /**
     * @param array|ProcessTrigger[] $storedTriggers
     * @param ProcessTrigger $builtTrigger
     *
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

    /**
     * @param ProcessTrigger $existingTrigger
     * @param ProcessTrigger $newTrigger
     */
    protected function update(ProcessTrigger $existingTrigger, ProcessTrigger $newTrigger)
    {
        $existingTrigger->import($newTrigger);
        $this->dirty = true;
        $this->notify('updated', $existingTrigger);
    }

    /**
     * @param ProcessTrigger $newTrigger
     */
    protected function create(ProcessTrigger $newTrigger)
    {
        $this->getObjectManager()->persist($newTrigger);
        $this->dirty = true;
        $this->notify('created', $newTrigger);
    }

    /**
     * @param ProcessTrigger $processTrigger
     */
    protected function delete(ProcessTrigger $processTrigger)
    {
        $this->getObjectManager()->remove($processTrigger);
        $this->dirty = true;
        $this->notify('deleted', $processTrigger);
    }

    /**
     * @param string $action
     * @param ProcessTrigger $trigger
     */
    private function notify($action, ProcessTrigger $trigger)
    {
        $this->logger->info(
            sprintf(
                '>> process trigger: %s [%s] - %s',
                $trigger->getDefinition() ? $trigger->getDefinition()->getName() : '',
                $trigger->getEvent() ?: 'cron:' . $trigger->getCron(),
                $action
            )
        );
    }

    /**
     * @param ProcessTrigger $trigger
     */
    protected function ensureSchedule(ProcessTrigger $trigger)
    {
        if ($trigger->getCron()) {
            $this->processCronScheduler->add($trigger);
        }
    }

    /**
     * @param ProcessTrigger $trigger
     */
    protected function dropSchedule(ProcessTrigger $trigger)
    {
        if ($trigger->getCron()) {
            $this->processCronScheduler->removeSchedule($trigger);
        }
    }

    /**
     * @param ProcessDefinition $definition
     */
    public function removeDefinitionTriggers(ProcessDefinition $definition)
    {
        foreach ($this->getRepository()->findByDefinition($definition) as $trigger) {
            $this->delete($trigger);
            $this->dropSchedule($trigger);
        }
    }

    public function flush()
    {
        if ($this->dirty) {
            $this->getObjectManager()->flush();
            $this->logger->info('>> process triggers modifications stored in DB');
            $this->dirty = false;
            foreach ($this->triggers as $trigger) {
                $this->ensureSchedule($trigger);
            }
            $this->processCronScheduler->flush();
        }
    }
}
