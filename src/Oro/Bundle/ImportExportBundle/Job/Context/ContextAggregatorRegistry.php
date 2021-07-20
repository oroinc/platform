<?php

namespace Oro\Bundle\ImportExportBundle\Job\Context;

use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The registry that allows to get the context aggregator by its type.
 */
class ContextAggregatorRegistry implements ResetInterface
{
    /** @var iterable|ContextAggregatorInterface[] */
    private $aggregators;

    /** @var ContextAggregatorInterface[]|null */
    private $initializedAggregators;

    /**
     * @param iterable|ContextAggregatorInterface[] $aggregators
     */
    public function __construct(iterable $aggregators)
    {
        $this->aggregators = $aggregators;
    }

    /**
     * Returns the context aggregator by its type.
     *
     * @throws RuntimeException if the aggregator for the given type does not exist
     */
    public function getAggregator(string $type): ContextAggregatorInterface
    {
        if (null === $this->initializedAggregators) {
            $this->initializedAggregators = [];
            foreach ($this->aggregators as $aggregator) {
                $this->initializedAggregators[$aggregator->getType()] = $aggregator;
            }
        }

        if (!isset($this->initializedAggregators[$type])) {
            throw new RuntimeException(sprintf('The context aggregator "%s" does not exist.', $type));
        }

        return $this->initializedAggregators[$type];
    }

    /**
     * {@inheritDoc}
     */
    public function reset()
    {
        $this->initializedAggregators = null;
    }
}
