<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * The default implementation of a factory to create filters.
 */
class SimpleFilterFactory implements FilterFactoryInterface
{
    private const SUPPORTED_OPERATORS_OPTION = 'supported_operators';

    /** @var PropertyAccessorInterface */
    protected $propertyAccessor;

    /** @var FilterOperatorRegistry */
    protected $filterOperatorRegistry;

    /** @var array [filter type => [class name, parameters], ...] */
    protected $filters = [];

    /** @var array [filter type => [factory service, factory method, parameters], ...] */
    protected $factories = [];

    /**
     * @param PropertyAccessorInterface $propertyAccessor
     * @param FilterOperatorRegistry    $filterOperatorRegistry
     */
    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        FilterOperatorRegistry $filterOperatorRegistry
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->filterOperatorRegistry = $filterOperatorRegistry;
    }

    /**
     * Registers a filter.
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
     * Registers a factory that should be used to create a filter.
     *
     * @param string $filterType    The type of a filter.
     * @param object $factory       The instance of a factory.
     * @param string $factoryMethod The name of a factory method.
     * @param array  $parameters    Additional parameters for the filter. [property name => value, ...]
     */
    public function addFilterFactory($filterType, $factory, $factoryMethod, array $parameters = [])
    {
        $refl = new \ReflectionClass($factory);
        if (!$refl->hasMethod($factoryMethod)
            || !$refl->getMethod($factoryMethod)->isPublic()
            || 1 !== $refl->getMethod($factoryMethod)->getNumberOfParameters()
        ) {
            throw new \InvalidArgumentException(\sprintf(
                'The "%s($dataType)" public method must be declared in the "%s" class.',
                $factoryMethod,
                get_class($factory)
            ));
        }
        $this->factories[$filterType] = [$factory, $factoryMethod, $parameters];
    }

    /**
     * {@inheritdoc}
     */
    public function createFilter($filterType, array $options = [])
    {
        if (!isset($this->factories[$filterType]) && !isset($this->filters[$filterType])) {
            return null;
        }

        $options = \array_replace($this->getFilterParameters($filterType), $options);
        $dataType = $filterType;
        if (\array_key_exists(self::DATA_TYPE_OPTION, $options)) {
            $dataType = $options[self::DATA_TYPE_OPTION];
            unset($options[self::DATA_TYPE_OPTION]);
        }

        if (!empty($options[self::SUPPORTED_OPERATORS_OPTION])) {
            $operators = [];
            foreach ($options[self::SUPPORTED_OPERATORS_OPTION] as $operator) {
                $operators[] = $this->filterOperatorRegistry->resolveOperator($operator);
            }
            $options[self::SUPPORTED_OPERATORS_OPTION] = $operators;
        }

        $filter = $this->instantiateFilter($filterType, $dataType);
        foreach ($options as $name => $value) {
            $this->propertyAccessor->setValue($filter, $name, $value);
        }

        return $filter;
    }

    /**
     * @param string $filterType
     * @param string $dataType
     *
     * @return object
     */
    protected function instantiateFilter($filterType, $dataType)
    {
        if (isset($this->factories[$filterType])) {
            list($factory, $factoryMethod) = $this->factories[$filterType];

            return $factory->$factoryMethod($dataType);
        }

        $filterClass = $this->filters[$filterType][0];

        return new $filterClass($dataType);
    }

    /**
     * @param string $filterType
     *
     * @return array
     */
    protected function getFilterParameters($filterType)
    {
        if (isset($this->factories[$filterType])) {
            return $this->factories[$filterType][2];
        }

        return $this->filters[$filterType][1];
    }
}
