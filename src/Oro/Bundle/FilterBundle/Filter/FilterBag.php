<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Psr\Container\ContainerInterface;

/**
 * The container for filters.
 */
class FilterBag implements FilterBagInterface
{
    /** @var string[] */
    private array $filterNames;
    private ContainerInterface $filterContainer;

    /**
     * @param string[]           $filterNames
     * @param ContainerInterface $filterContainer
     */
    public function __construct(array $filterNames, ContainerInterface $filterContainer)
    {
        $this->filterNames = $filterNames;
        $this->filterContainer = $filterContainer;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterNames(): array
    {
        return $this->filterNames;
    }

    /**
     * {@inheritdoc}
     */
    public function hasFilter(string $name): bool
    {
        return $this->filterContainer->has($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilter(string $name): FilterInterface
    {
        /** @var FilterInterface $filter */
        $filter = $this->filterContainer->get($name);
        $filter->reset();

        return $filter;
    }
}
