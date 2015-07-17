<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\MailboxProcessorSettings as ProcessorEntity;

class MailboxProcessorProvider
{
    /** @var MailboxProcessorInterface[] */
    protected $processorTypes;

    /** @var MailboxProcessorInterface[] */
    protected $processors;

    public function __construct()
    {
        $this->processorTypes = [];
        $this->processors = [];
    }

    /**
     * Adds new processor type.
     *
     * @param MailboxProcessorInterface $instance
     * @param string                    $type
     */
    public function addProcessorType(MailboxProcessorInterface $instance, $type)
    {
        $this->processorTypes[$type] = $instance;
    }

    /**
     * Returns processor of given type
     *
     * @param ProcessorEntity $entity
     *
     * @return MailboxProcessorInterface
     */
    public function getProcessor(ProcessorEntity $entity)
    {
        if (!isset($this->processors[$entity->getId()])) {
            if (!isset($this->processorTypes[$entity->getType()])) {
                throw new \LogicException(
                    "MailboxProcessor type {$entity->getType()} is not registered. Check if appropriate service exists."
                );
            }

            $processor = clone $this->processorTypes[$entity->getType()];
            $processor->configureFromEntity($entity);
            $this->processors[$entity->getId()] = $processor;
        }

        return $this->processors[$entity->getId()];
    }

    /**
     * @return MailboxProcessorInterface[]
     */
    public function getProcessorTypes()
    {
        return $this->processorTypes;
    }

    /**
     * Returns choice list with all processor types.
     *
     * @return array('type' => 'label')
     */
    public function getProcessorTypesChoiceList()
    {
        $choices = [];

        foreach ($this->processorTypes as $processorType) {
            $choices[$processorType->getType()] = $processorType->getLabel();
        }

        return $choices;
    }

    /**
     * Creates an instance of configuration entity for provided processor type.
     *
     * @param MailboxProcessorInterface|string $type
     *
     * @return mixed
     */
    public function createConfigurationEntity($type)
    {
        if (!($type instanceof MailboxProcessorInterface)) {
            $type = $this->processorTypes[$type];
        }

        $entityName = $type->getEntityFQCN();

        if (!class_exists($entityName)) {
            throw new \LogicException("Entity: {$entityName} does not exist.");
        }

        $instance = new $entityName();

        return $instance;
    }
}
