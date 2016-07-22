<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ActionProcessorInterface;

class ActionProcessorBag implements ActionProcessorBagInterface
{
    /** @var ActionProcessorInterface[] */
    protected $processors = [];

    /**
     * {@inheritdoc}
     */
    public function addProcessor(ActionProcessorInterface $processor)
    {
        $this->processors[$processor->getAction()] = $processor;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessor($action)
    {
        if (!isset($this->processors[$action])) {
            throw new \InvalidArgumentException(sprintf('A processor for "%s" action was not found.', $action));
        }

        return $this->processors[$action];
    }

    /**
     * {@inheritdoc}
     */
    public function getActions()
    {
        return array_keys($this->processors);
    }
}
