<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ActionProcessorInterface;

/**
 * Represents a storage for processors for all registered actions.
 */
interface ActionProcessorBagInterface
{
    /**
     * Registers a processor in the bag.
     */
    public function addProcessor(ActionProcessorInterface $processor): void;

    /**
     * Gets a processor responsible to handle the given action.
     *
     * @throws \InvalidArgumentException if a processor for the given action was not found
     */
    public function getProcessor(string $action): ActionProcessorInterface;

    /**
     * Gets all actions registered in the bag.
     *
     * @return string[]
     */
    public function getActions(): array;
}
