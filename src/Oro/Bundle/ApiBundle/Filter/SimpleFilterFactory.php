<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * The default implementation of a factory to create filters.
 */
class SimpleFilterFactory implements FilterFactoryInterface
{
    private const SUPPORTED_OPERATORS_OPTION = 'supported_operators';

    private PropertyAccessorInterface $propertyAccessor;
    private FilterOperatorRegistry $filterOperatorRegistry;
    /** @var array [filter type => [class name, parameters], ...] */
    private array $filters;
    /** @var array [filter type => [factory service id, factory method, parameters], ...] */
    private array $factories;
    private ContainerInterface $factoryContainer;

    /**
     * @param array                     $filters         [filter type => [class name, params], ...]
     * @param array                     $filterFactories [filter type => [service id, method name, params], ...]
     * @param ContainerInterface        $filterFactoryContainer
     * @param PropertyAccessorInterface $propertyAccessor
     * @param FilterOperatorRegistry    $filterOperatorRegistry
     */
    public function __construct(
        array $filters,
        array $filterFactories,
        ContainerInterface $filterFactoryContainer,
        PropertyAccessorInterface $propertyAccessor,
        FilterOperatorRegistry $filterOperatorRegistry
    ) {
        $this->filters = $filters;
        $this->factories = $filterFactories;
        $this->factoryContainer = $filterFactoryContainer;
        $this->propertyAccessor = $propertyAccessor;
        $this->filterOperatorRegistry = $filterOperatorRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function createFilter(string $filterType, array $options = []): ?StandaloneFilter
    {
        if (!isset($this->factories[$filterType]) && !isset($this->filters[$filterType])) {
            return null;
        }

        $options = array_replace($this->getFilterParameters($filterType), $options);
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

    private function instantiateFilter(string $filterType, string $dataType): StandaloneFilter
    {
        if (isset($this->factories[$filterType])) {
            [$factoryId, $factoryMethod] = $this->factories[$filterType];
            $factory = $this->factoryContainer->get($factoryId);
            $filter = $factory->$factoryMethod($dataType);
        } else {
            $filterClass = $this->filters[$filterType][0];
            $filter = new $filterClass($dataType);
        }
        if (!$filter instanceof StandaloneFilter) {
            throw new \LogicException(sprintf(
                'The filter "%s" must be an instance of %s, got %s.',
                $filterType,
                StandaloneFilter::class,
                \get_class($filter)
            ));
        }

        return $filter;
    }

    private function getFilterParameters(string $filterType): array
    {
        if (isset($this->factories[$filterType])) {
            return $this->factories[$filterType][2];
        }

        return $this->filters[$filterType][1];
    }
}
