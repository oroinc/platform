<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface;
use Oro\Bundle\FilterBundle\Filter\FilterBagInterface;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides a set of methods to build a query based on the query designer configuration.
 */
class Manager implements FunctionProviderInterface
{
    /** @var ConfigurationProvider */
    private $configProvider;

    /** @var ConfigurationResolver */
    private $configResolver;

    /** @var EntityHierarchyProviderInterface */
    private $entityHierarchyProvider;

    /** @var TranslatorInterface */
    private $translator;

    /** @var FilterBagInterface */
    private $filterBag;

    /** @var ConfigurationObject|null */
    private $config;

    public function __construct(
        ConfigurationProvider $configProvider,
        ConfigurationResolver $configResolver,
        EntityHierarchyProviderInterface $entityHierarchyProvider,
        FilterBagInterface $filterBag,
        TranslatorInterface $translator
    ) {
        $this->configProvider = $configProvider;
        $this->configResolver = $configResolver;
        $this->entityHierarchyProvider = $entityHierarchyProvider;
        $this->filterBag = $filterBag;
        $this->translator = $translator;
    }

    /**
     * Returns metadata for the given query type
     *
     * @param string $queryType The query type
     *
     * @return array
     */
    public function getMetadata($queryType)
    {
        $filtersMetadata = [];
        $filters = $this->getFilters($queryType);
        foreach ($filters as $filter) {
            $filtersMetadata[] = $filter->getMetadata();
        }

        return [
            'filters'    => $filtersMetadata,
            'grouping'   => $this->getMetadataForGrouping(),
            'converters' => $this->getMetadataForFunctions('converters', $queryType),
            'aggregates' => $this->getMetadataForFunctions('aggregates', $queryType),
            'hierarchy'  => $this->entityHierarchyProvider->getHierarchy()
        ];
    }

    /**
     * Creates a new instance of a filter based on a configuration
     * of a filter registered in this manager with the given name
     *
     * @param string     $name   A filter name
     * @param array|null $params An additional parameters of a new filter
     *
     * @return FilterInterface
     *
     * @throws \RuntimeException if a filter with the given name does not exist
     */
    public function createFilter($name, array $params = null)
    {
        $filtersConfig = $this->getConfig()->offsetGet('filters');
        if (!isset($filtersConfig[$name])) {
            throw new \RuntimeException(sprintf('Unknown filter "%s".', $name));
        }

        $config = $filtersConfig[$name];
        if ($params !== null && !empty($params)) {
            $config = array_merge($config, $params);
        }

        return $this->getFilterObject($name, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function getFunction($name, $groupName, $groupType)
    {
        $result = null;
        $functions = $this->getConfig()->offsetGetByPath(sprintf('[%s][%s][functions]', $groupType, $groupName));
        if ($functions !== null) {
            foreach ($functions as $function) {
                if ($function['name'] === $name) {
                    $result = $function;
                    break;
                }
            }
        }
        if ($result === null) {
            throw new InvalidConfigurationException(
                sprintf('The function "%s:%s:%s" was not found.', $groupType, $groupName, $name)
            );
        }

        return $result;
    }

    /**
     * Returns filters types
     *
     * @param string[] $filterNames
     *
     * @return array
     */
    public function getExcludedProperties(array $filterNames)
    {
        $types = [];
        $filters = $this->getConfig()->offsetGet('filters');
        foreach ($filterNames as $filterName) {
            unset($filters[$filterName]);
        }

        foreach ($filters as $filter) {
            if (isset($filter['applicable'])) {
                foreach ($filter['applicable'] as $type) {
                    $types[] = $type;
                }
            }
        }

        return $types;
    }

    /**
     * Returns all available filters for the given query type
     *
     * @param string $queryType The query type
     *
     * @return FilterInterface[]
     */
    private function getFilters($queryType)
    {
        $filters = [];
        $filtersConfig = $this->getConfig()->offsetGet('filters');
        foreach ($filtersConfig as $name => $attr) {
            if ($this->isItemAllowedForQueryType($attr, $queryType)) {
                unset($attr['query_type']);
                $filters[$name] = $this->getFilterObject($name, $attr);
            }
        }

        return $filters;
    }

    /**
     * Returns prepared filter object
     *
     * @param string $name
     * @param array  $config
     *
     * @return FilterInterface
     */
    private function getFilterObject($name, array $config)
    {
        $filter = clone $this->filterBag->getFilter($config['type']);
        $filter->init($name, $config);

        return $filter;
    }

    /**
     * Returns grouping metadata
     *
     * @return array
     */
    public function getMetadataForGrouping()
    {
        return $this->getConfig()->offsetGet('grouping');
    }

    /**
     * Returns all available functions for the given query type
     *
     * @param string $groupType The type of functions' group
     * @param string $queryType The query type
     *
     * @return array
     */
    public function getMetadataForFunctions($groupType, $queryType)
    {
        $result = [];
        $groupsConfig = $this->getConfig()->offsetGet($groupType);
        foreach ($groupsConfig as $name => $attr) {
            if ($this->isItemAllowedForQueryType($attr, $queryType)) {
                unset($attr['query_type']);
                $functions = [];
                foreach ($attr['functions'] as $function) {
                    $nameText    = empty($function['name_label'])
                        ? null // if a label is empty it means that this function should inherit a label
                        : $this->translator->trans($function['name_label']);
                    $hintText    = empty($function['hint_label'])
                        ? null // if a label is empty it means that this function should inherit a label
                        : $this->translator->trans($function['hint_label']);
                    $func = [
                        'name'  => $function['name'],
                        'label' => $nameText,
                        'title' => $hintText,
                    ];
                    if (isset($function['return_type'])) {
                        $func['return_type'] = $function['return_type'];
                    }

                    $functions[] = $func;
                }
                $attr['functions'] = $functions;
                $result[$name]     = $attr;
            }
        }

        return $result;
    }

    /**
     * Checks if an item can be used for the given query type
     *
     * @param array  $item      An item to check
     * @param string $queryType The query type
     *
     * @return bool true if the item can be used for the given query type; otherwise, false.
     */
    private function isItemAllowedForQueryType($item, $queryType)
    {
        foreach ($item['query_type'] as $itemQueryType) {
            if ($itemQueryType === 'all' || $itemQueryType === $queryType) {
                return true;
            }
        }

        return false;
    }

    private function getConfig(): ConfigurationObject
    {
        if (null === $this->config) {
            $config = $this->configProvider->getConfiguration();
            $this->configResolver->resolve($config);
            $this->config = ConfigurationObject::create($config);
        }

        return $this->config;
    }
}
