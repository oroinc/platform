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
     * @param string $filterType      The type of a filter.
     * @param string $filterClassName The class name of a filter. Should extents StandaloneFilter.
     * @param array  $parameters      Additional parameters for the filter. [property name => value, ...]
     */
    public function addFilter($filterType, $filterClassName, array $parameters = [])
    {
        $this->filters[$filterType] = [$filterClassName, $parameters];
    }

    /**
     * {@inheritdoc}
     */
    public function createFilter($filterType, array $options = [])
    {
        if (!isset($this->filters[$filterType])) {
            return null;
        }

        list($filterClassName, $parameters) = $this->filters[$filterType];
        $options = array_replace($parameters, $options);
        $dataType = $filterType;
        if (array_key_exists(self::DATA_TYPE_OPTION, $options)) {
            $dataType = $options[self::DATA_TYPE_OPTION];
            unset($options[self::DATA_TYPE_OPTION]);
        }
        $filter = new $filterClassName($dataType);
        foreach ($options as $name => $value) {
            $this->propertyAccessor->setValue($filter, $name, $value);
        }

        return $filter;
    }
}
