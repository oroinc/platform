<?php

namespace Oro\Bundle\ApiBundle\Filter;

class SimpleFilterFactory implements FilterFactoryInterface
{
    /** @var array [data_type => class_name, ...] */
    protected $filters = [];

    /**
     * Registers a filter that should be used to handle the given data-type.
     *
     * @param string $dataType        The data-type of a value.
     * @param string $filterClassName The class name of a filter. Should extents StandaloneFilter.
     */
    public function addFilter($dataType, $filterClassName)
    {
        $this->filters[$dataType] = $filterClassName;
    }

    /**
     * {@inheritdoc}
     */
    public function createFilter($dataType)
    {
        if (!isset($this->filters[$dataType])) {
            return null;
        }

        $filterClassName = $this->filters[$dataType];

        return new $filterClassName($dataType);
    }
}
