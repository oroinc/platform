<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * The base context for classes that convert a query definition created by the query designer to an ORM query.
 */
class GroupingOrmQueryConverterContext extends QueryConverterContext
{
    /** @var PropertyAccessor */
    private $propertyAccessor;

    /** @var array */
    private $filters = [];

    /** string */
    private $currentFilterPath = '';

    public function __construct()
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        parent::reset();
        $this->filters = [];
        $this->currentFilterPath = '';
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function addFilter(array $filter): void
    {
        $this->propertyAccessor->setValue($this->filters, $this->currentFilterPath, $filter);
        $this->incrementCurrentFilterPath();
    }

    public function addFilterOperator(string $operator): void
    {
        $this->propertyAccessor->setValue($this->filters, $this->currentFilterPath, $operator);
        $this->incrementCurrentFilterPath();
    }

    public function beginFilterGroup(): void
    {
        $this->currentFilterPath .= '[0]';
        $this->propertyAccessor->setValue($this->filters, $this->currentFilterPath, []);
    }

    public function endFilterGroup(): void
    {
        $this->currentFilterPath = substr(
            $this->currentFilterPath,
            0,
            strrpos($this->currentFilterPath, '[')
        );
        if ($this->currentFilterPath !== '') {
            $this->incrementCurrentFilterPath();
        }
    }

    /**
     * Increments the last index in the path to the current filter.
     */
    private function incrementCurrentFilterPath(): void
    {
        $start = strrpos($this->currentFilterPath, '[');
        $index = substr(
            $this->currentFilterPath,
            $start + 1,
            \strlen($this->currentFilterPath) - $start - 2
        );
        $this->currentFilterPath = sprintf(
            '%s%d]',
            substr($this->currentFilterPath, 0, $start + 1),
            (int)$index + 1
        );
    }
}
