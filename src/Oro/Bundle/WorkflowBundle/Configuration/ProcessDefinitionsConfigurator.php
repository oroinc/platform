<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;

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
     * @param string|array $names
     */
    public function removeDefinitions($names)
    {
        foreach ((array)$names as $name) {
            $this->removeDefinition($name);
        }
    }

    /**
     * @param string $name
     */
    public function removeDefinition($name)
    {
        /**@var ProcessDefinition $definition */
        $definition = $this->getRepository()->find($name);
        $this->getObjectManager()->remove($definition);
        $this->dirty = true;
        $this->notify('deleted', $definition);
    }

    /**
     * @param ProcessDefinition $existingDefinition
     * @param ProcessDefinition $newDefinition
     */
    protected function update(ProcessDefinition $existingDefinition, ProcessDefinition $newDefinition)
    {
        $existingDefinition->import($newDefinition);
        $this->dirty = true;
        $this->notify('updated', $existingDefinition);
    }

    /**
     * @param ProcessDefinition $newDefinition
     */
    protected function create(ProcessDefinition $newDefinition)
    {
        $this->getObjectManager()->persist($newDefinition);
        $this->dirty = true;
        $this->notify('created', $newDefinition);
    }

    public function flush()
    {
        if ($this->dirty) {
            $this->getObjectManager()->flush();
            $this->logger->info('Process definitions configuration updates are stored into database');
            $this->dirty = false;
        } else {
            $this->logger->info('No process definitions configuration updates detected. Nothing flushed into DB');
        }
    }

    /**
     * @param string $action
     * @param ProcessDefinition $definition
     */
    protected function notify($action, ProcessDefinition $definition)
    {
        $this->logger->info(sprintf('> process definition: "%s" - %s', $definition->getName(), $action));
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        if ($this->objectManager) {
            return $this->objectManager;
        }

        return $this->objectManager = $this->registry->getManagerForClass($this->definitionClass);
    }

    /**
     * @return ObjectRepository
     */
    protected function getRepository()
    {
        return $this->getObjectManager()->getRepository($this->definitionClass);
    }
}
