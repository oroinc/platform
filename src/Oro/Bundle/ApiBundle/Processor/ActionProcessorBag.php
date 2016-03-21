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
        $this->processors[] = $processor;
    }

    /**
     * {@inheritdoc}
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
