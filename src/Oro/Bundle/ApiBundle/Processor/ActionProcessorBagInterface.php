<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ActionProcessorInterface;

interface ActionProcessorBagInterface
{
    /**
     * Registers a processor in the bag.
     *
     * @param ActionProcessorInterface $processor
     */
    public function addProcessor(ActionProcessorInterface $processor);

    /**
     * Gets a processor responsible to handle the given action.
     *
     * @param string $action
     *
     * @return ActionProcessorInterface
     *
     * @throws \InvalidArgumentException if a processor for the given action was not found
     */
    public function getProcessor($action);

    /**
     * Gets all actions registered in the bag.
     *
     * @return string[]
     */
    public function getActions();
}
