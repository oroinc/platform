<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ActionProcessor;

class ActionProcessorBag
{
    /** @var ActionProcessor[] */
    protected $processors = [];

    /**
     * Registers a processor in the bag.
     *
     * @param ActionProcessor $processor
     */
    public function addProcessor(ActionProcessor $processor)
    {
        $this->processors[] = $processor;
    }

    /**
     * Gets a processor responsible to handle the given action.
     *
     * @param string $action
     *
     * @return ActionProcessor
     */
    public function getProcessor($action)
    {
        foreach ($this->processors as $processor) {
            if ($processor->getAction() === $action) {
                return $processor;
            }
        }

        throw new \InvalidArgumentException(sprintf('A processor for "%s" action was not found.', $action));
    }
}
