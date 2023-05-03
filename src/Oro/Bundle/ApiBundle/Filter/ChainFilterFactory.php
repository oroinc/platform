<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * Delegates the creation of filters to child factories.
 */
class ChainFilterFactory implements FilterFactoryInterface
{
    /** @var iterable<FilterFactoryInterface> */
    private iterable $factories;

    /**
     * @param iterable<FilterFactoryInterface> $factories
     */
    public function __construct(iterable $factories)
    {
        $this->factories = $factories;
    }

    /**
     * {@inheritDoc}
     */
    public function createFilter(string $filterType, array $options = []): ?StandaloneFilter
    {
        foreach ($this->factories as $factory) {
            $filter = $factory->createFilter($filterType, $options);
            if (null !== $filter) {
                return $filter;
            }
        }

        return null;
    }
}
