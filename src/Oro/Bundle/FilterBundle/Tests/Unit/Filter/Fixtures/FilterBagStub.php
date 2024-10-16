<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter\Fixtures;

use Oro\Bundle\FilterBundle\Filter\FilterBagInterface;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;

class FilterBagStub implements FilterBagInterface
{
    /** @var FilterInterface[] [filter name => filter, ...] */
    private $filters = [];

    public function addFilter(string $name, FilterInterface $filter): void
    {
        $this->filters[$name] = $filter;
    }

    #[\Override]
    public function getFilterNames(): array
    {
        return array_keys($this->filters);
    }

    #[\Override]
    public function hasFilter(string $name): bool
    {
        return isset($this->filters[$name]);
    }

    #[\Override]
    public function getFilter(string $name): FilterInterface
    {
        return $this->filters[$name];
    }
}
