<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class SimpleFilterFactory implements FilterFactoryInterface
{
    /** @var PropertyAccessorInterface */
    protected $propertyAccessor;

    /** @var array [data_type => [class_name, parameters], ...] */
    protected $filters = [];

    /**
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * Registers a filter that should be used to handle the given data-type.
     *
     * @param string $dataType        The data-type of a value.
     * @param string $filterClassName The class name of a filter. Should extents StandaloneFilter.
     * @param array  $parameters      Additional parameters for the filter. [property name => value, ...]
     */
    public function addFilter($dataType, $filterClassName, array $parameters = [])
    {
        $this->filters[$dataType] = [$filterClassName, $parameters];
    }

    /**
     * {@inheritdoc}
     */
    public function createFilter($dataType)
    {
        if (!isset($this->filters[$dataType])) {
            return null;
        }

        $options = $this->filters[$dataType];
        $filterClassName = $options[0];
        $filter = new $filterClassName($dataType);
        if (!empty($options[1])) {
            foreach ($options[1] as $name => $value) {
                $this->propertyAccessor->setValue($filter, $name, $value);
            }
        }

        return $filter;
    }
}
