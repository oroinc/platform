<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class ProcessDefinitionsConfigurator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var ProcessConfigurationBuilder */
    private $configurationBuilder;

    /** @var ManagerRegistry */
    private $registry;

    /** @var string */
    private $definitionClass;

    /** @var ObjectManager */
    private $objectManager;

    /** @var array|ProcessDefinition[] */
    private $toPersist = [];

    /** @var array|ProcessDefinition[] */
    private $toRemove = [];

    /** @var bool */
    private $dirty = false;

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
        $this->setLogger(new NullLogger());
    }

    /**
     * @param array $definitionsConfiguration
     */
    public function configureDefinitions(array $definitionsConfiguration)
    {
        $definitionRepository = $this->getRepository();

        $definitions = $this->configurationBuilder->buildProcessDefinitions($definitionsConfiguration);

        if ($definitions) { #because of flush
            foreach ($definitions as $newDefinition) {
                $definitionName = $newDefinition->getName();
                /** @var ProcessDefinition $existingDefinition */
                // definition should be overridden if definition with such name already exists
                $existingDefinition = $definitionRepository->find($definitionName);
                if ($existingDefinition) {
                    $this->update($existingDefinition, $newDefinition);
                } else {
                    $this->create($newDefinition);
                }
            }
        }
    }

    /**
     * @param string $name
     */
    public function removeDefinition($name)
    {
        /**@var ProcessDefinition $definition */
        $definition = $this->getRepository()->find($name);
        $this->toRemove[] = $definition;
        $this->dirty = true;
        $this->notify($definition, 'deleted');
    }

    /**
     * @param ProcessDefinition $existingDefinition
     * @param ProcessDefinition $newDefinition
     */
    protected function update(ProcessDefinition $existingDefinition, ProcessDefinition $newDefinition)
    {
        $existingDefinition->import($newDefinition);
        $this->dirty = true;
        $this->notify($existingDefinition, 'updated');
    }

    /**
     * @param ProcessDefinition $newDefinition
     */
    protected function create(ProcessDefinition $newDefinition)
    {
        $this->toPersist[] = $newDefinition;
        $this->dirty = true;
        $this->notify($newDefinition, 'created');
    }

    public function flush()
    {
        if ($this->dirty) {
            $objectManager = $this->getObjectManager();
            while ($createdDefinition = array_shift($this->toPersist)) {
                $objectManager->persist($createdDefinition);
            }
            while ($definitionToDelete = array_shift($this->toRemove)) {
                if ($objectManager->contains($definitionToDelete)) {
                    $objectManager->remove($definitionToDelete);
                }
            }

            $objectManager->flush();
            $this->dirty = false;
            $message = 'Process definitions configuration updates are stored into database';
        } else {
            $message = 'No process definitions configuration updates detected. Nothing flushed into DB';
        }

        if ($this->logger) {
            $this->logger->info($message);
        }
    }

    /**
     * @param ProcessDefinition $definition
     * @param string $action
     */
    protected function notify(ProcessDefinition $definition, $action)
    {
        if (!$this->logger) {
            return;
        }

        $this->logger->info(
            '> process definition: "{definition_name}" - {action}',
            [
                'definition_name' => $definition->getName(),
                'action' => $action
            ]
        );
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        if (!$this->objectManager) {
            $this->objectManager = $this->registry->getManagerForClass($this->definitionClass);
        }

        return $this->objectManager;
    }

    /**
     * @return ObjectRepository
     */
    protected function getRepository()
    {
        return $this->getObjectManager()->getRepository($this->definitionClass);
    }
}
