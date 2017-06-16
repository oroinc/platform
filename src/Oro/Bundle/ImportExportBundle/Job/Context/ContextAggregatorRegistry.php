<?php

namespace Oro\Bundle\ImportExportBundle\Job\Context;

use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;

class ContextAggregatorRegistry
{
    /** @var ContextAggregatorInterface[] */
    protected $aggregators;

    /**
     * Returns aggregator by type
     *
     * @param string $type
     *
     * @return ContextAggregatorInterface
     *
     * @throws RuntimeException
     */
    public function getAggregator($type)
    {
        if (!array_key_exists($type, $this->aggregators)) {
            throw new RuntimeException(
                sprintf('The context aggregator "%s" does not exist.', $type)
            );
        }

        return $this->aggregators[$type];
    }

    /**
     * @param ContextAggregatorInterface $aggregator
     */
    public function addAggregator(ContextAggregatorInterface $aggregator)
    {
        $this->aggregators[$aggregator->getType()] = $aggregator;
    }
}
