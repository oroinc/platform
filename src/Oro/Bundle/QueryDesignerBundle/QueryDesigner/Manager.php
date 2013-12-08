<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Oro\Bundle\QueryDesignerBundle\Provider\SystemAwareResolver;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Symfony\Component\Translation\Translator;

class Manager
{
    /** @var ConfigurationObject */
    protected $config;

    /** @var FilterInterface[] */
    protected $filters = [];

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * Constructor
     *
     * @param array               $config
     * @param SystemAwareResolver $resolver
     * @param Translator          $translator
     */
    public function __construct(
        array $config,
        SystemAwareResolver $resolver,
        Translator $translator
    ) {
        $resolver->resolve($config);
        $this->config     = ConfigurationObject::create($config);
        $this->translator = $translator;
    }

    /**
     * Returns metadata for the given query type
     *
     * @param string $queryType The query type
     * @return array
     */
    public function getMetadata($queryType)
    {
        $filtersMetadata = [];
        $filters         = $this->getFilters($queryType);
        foreach ($filters as $filter) {
            $filtersMetadata[] = $filter->getMetadata();
        }

        return [
            'filters'    => $filtersMetadata,
            'grouping'   => $this->getMetadataForGrouping(),
            'converters' => $this->getMetadataForFunctions('converters', $queryType),
            'aggregates' => $this->getMetadataForFunctions('aggregates', $queryType)
        ];
    }

    /**
     * Add filter to array of available filters
     *
     * @param string          $type
     * @param FilterInterface $filter
     */
    public function addFilter($type, FilterInterface $filter)
    {
        $this->filters[$type] = $filter;
    }

    /**
     * Creates a new instance of a filter based on a configuration
     * of a filter registered in this manager with the given name
     *
     * @param string $name   A filter name
     * @param array  $params An additional parameters of a new filter
     * @throws \RuntimeException if a filter with the given name does not exist
     * @return FilterInterface
     */
    public function createFilter($name, array $params = null)
    {
        $config        = null;
        $filtersConfig = $this->config->offsetGet('filters');
        foreach ($filtersConfig as $filterName => $attr) {
            if ($filterName === $name) {
                $config = $attr;
                break;
            }
        }
        if ($config === null) {
            throw new \RuntimeException(sprintf('Unknown filter "%s".', $name));
        }

        if ($params !== null && !empty($params)) {
            $config = array_merge($config, $params);
        }

        return $this->getFilterObject($name, $config);
    }

    /**
     * Returns all available filters for the given query type
     *
     * @param string $queryType The query type
     * @return FilterInterface[]
     */
    protected function getFilters($queryType)
    {
        $filters       = [];
        $filtersConfig = $this->config->offsetGet('filters');
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
    protected function getFilterObject($name, array $config)
    {
        $filter = clone $this->filters[$config['type']];
        $filter->init($name, $config);

        return $filter;
    }

    /**
     * Returns grouping metadata
     *
     * @return array
     */
    protected function getMetadataForGrouping()
    {
        return $this->config->offsetGet('grouping');
    }

    /**
     * Returns all available functions for the given query type
     *
     * @param string $groupType The type of functions' group
     * @param string $queryType The query type
     * @return array
     */
    protected function getMetadataForFunctions($groupType, $queryType)
    {
        $result       = [];
        $groupsConfig = $this->config->offsetGet($groupType);
        foreach ($groupsConfig as $name => $attr) {
            if ($this->isItemAllowedForQueryType($attr, $queryType)) {
                unset($attr['query_type']);
                $functions = [];
                foreach ($attr['functions'] as $function) {
                    $functions[] = [
                        'name'  => $function['name'],
                        'label' => $this->translator->trans($function['label']),
                    ];
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
     * @return bool true if the item can be used for the given query type; otherwise, false.
     */
    protected function isItemAllowedForQueryType(&$item, $queryType)
    {
        foreach ($item['query_type'] as $itemQueryType) {
            if ($itemQueryType === 'all' || $itemQueryType === $queryType) {
                return true;
            }
        }

        return false;
    }
}
