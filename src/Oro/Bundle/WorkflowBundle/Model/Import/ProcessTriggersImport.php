<?php

namespace Oro\Bundle\WorkflowBundle\Model\Import;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository;
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
     * @param ProcessConfigurationBuilder $configurationBuilder
     * @param ManagerRegistry $managerRegistry
     * @param string $triggerEntityClass
     */
    public function __construct(
        ProcessConfigurationBuilder $configurationBuilder,
        ManagerRegistry $managerRegistry,
        $triggerEntityClass
    ) {
        $this->configurationBuilder = $configurationBuilder;
        $this->managerRegistry = $managerRegistry;
        $this->triggerEntityClass = $triggerEntityClass;
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

        if ($triggers) { #because of flush
            $entityManager = $this->getObjectManager();
            /** @var ProcessTriggerRepository $triggerRepository */
            $triggerRepository = $this->getRepository();
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
     * @return \Doctrine\ORM\EntityRepository|ProcessTriggerRepository
     */
    public function getTriggersRepository()
    {
        return $this->getRepository();
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
