<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration;
use Oro\Bundle\DataGridBundle\Provider\SystemAwareResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractConfigGridListener
{
    const TYPE_HTML     = 'html';
    const TYPE_TWIG     = 'twig';
    const TYPE_NAVIGATE = 'navigate';
    const TYPE_DELETE   = 'delete';

    const PATH_COLUMNS  = '[columns]';
    const PATH_SORTERS  = '[sorters]';
    const PATH_FILTERS  = '[filters]';
    const PATH_ACTIONS  = '[actions]';

    /** @var ConfigManager */
    protected $configManager;

    /** @var SystemAwareResolver */
    protected $datagridResolver;

    /**
     * @param ConfigManager       $configManager
     * @param SystemAwareResolver $datagridResolver
     */
    public function __construct(ConfigManager $configManager, SystemAwareResolver $datagridResolver)
    {
        $this->configManager    = $configManager;
        $this->datagridResolver = $datagridResolver;
    }

    /**
     * @param BuildAfter $event
     */
    abstract public function onBuildAfter(BuildAfter $event);

    /**
     * @param BuildBefore $event
     */
    abstract public function onBuildBefore(BuildBefore $event);

    /**
     * Add dynamic fields
     *
     * @param BuildBefore $event
     * @param string|null $alias
     * @param string|null $itemType
     * @param bool        $dynamicFirst flag if true - dynamic columns will be placed before static, false - after
     */
    public function doBuildBefore(BuildBefore $event, $alias = null, $itemType = null, $dynamicFirst = true)
    {
        $config = $event->getConfig();

        // get dynamic columns and merge them with static columns from configuration
        $additionalColumnSettings = $this->getDynamicFields($alias, $itemType);
        $filtersSorters           = $this->getDynamicSortersAndFilters($additionalColumnSettings);
        $additionalColumnSettings = [
            'columns' => $additionalColumnSettings,
            'sorters' => $filtersSorters['sorters'],
            'filters' => $filtersSorters['filters'],
        ];

        $additionalColumnSettings = $this->datagridResolver->resolve($config->getName(), $additionalColumnSettings);

        foreach (['columns', 'sorters', 'filters'] as $itemName) {
            $path = '[' . $itemName . ']';
            // get already defined items
            $items = $config->offsetGetByPath($path, []);

            // merge additional items with existing
            if ($dynamicFirst) {
                $items = array_merge_recursive($additionalColumnSettings[$itemName], $items);
            } else {
                $items = array_merge_recursive($items, $additionalColumnSettings[$itemName]);
            }

            // set new item set with dynamic columns/sorters/filters
            $config->offsetSetByPath($path, $items);
        }

        // add/configure entity config properties
        $this->addEntityConfigProperties($config, $itemType);

        // add/configure entity config actions
        $actions = $config->offsetGetByPath(self::PATH_ACTIONS, []);
        $this->prepareRowActions($actions, $itemType);
        $config->offsetSetByPath(self::PATH_ACTIONS, $actions);
    }

    /**
     * Get dynamic fields from config providers
     *
     * @param string|null $alias
     * @param string|null $itemsType
     *
     * @return array
     */
    protected function getDynamicFields($alias = null, $itemsType = null)
    {
        $fields = [];

        $providers = $this->configManager->getProviders();
        foreach ($providers as $provider) {
            $configItems = $provider->getPropertyConfig()->getItems($itemsType);
            foreach ($configItems as $code => $item) {
                if (!isset($item['grid'])) {
                    continue;
                }

                $fieldName    = $provider->getScope() . '_' . $code;
                $item['grid'] = $this->mapEntityConfigTypes($item['grid']);

                $attributes = ['field_name' => $fieldName];
                if (isset($item['options']['indexed']) && $item['options']['indexed']) {
                    $attributes['expression'] = $alias . $provider->getScope() . '_' . $code . '.value';
                } else {
                    $attributes['data_name'] = '[data][' . $provider->getScope() . '][' . $code . ']';
                }
                $field = [
                    $fieldName => array_merge($item['grid'], $attributes)
                ];

                if (isset($item['options']['priority']) && !isset($fields[$item['options']['priority']])) {
                    $fields[$item['options']['priority']] = $field;
                } else {
                    $fields[] = $field;
                }
            }
        }

        // sort by priority and flatten
        ksort($fields);
        $fields = call_user_func_array('array_merge', $fields);

        return $fields;
    }

    /**
     * @param array $orderedFields
     *
     * @return array
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getDynamicSortersAndFilters(array $orderedFields)
    {
        $filters = $sorters = [];

        // add sorters and filters if needed
        foreach ($orderedFields as $fieldName => $field) {
            if (isset($field['sortable']) && $field['sortable']) {
                $sorters['columns'][$fieldName] = [
                    'data_name'      => isset($field['expression']) ? $field['expression'] : null,
                    'apply_callback' => function (OrmDatasource $datasource, $sortKey, $direction) {
                        if ($sortKey) {
                            QueryBuilderUtil::checkField($sortKey);
                            $datasource->getQueryBuilder()
                                ->addOrderBy($sortKey, QueryBuilderUtil::getSortOrder($direction));
                        }
                    }
                ];
            }

            if (isset($field['filterable']) && $field['filterable']) {
                $filters['columns'][$fieldName] = [
                    'data_name'                => isset($field['expression']) ? $field['expression'] : $fieldName,
                    'type'                     => isset($field['filter_type']) ? $field['filter_type'] : 'string',
                    'frontend_type'            => $field['frontend_type'],
                    'label'                    => $field['label'],
                    'options'                  => isset($field['filter_options']) ? $field['filter_options'] : [],
                    FilterUtility::ENABLED_KEY => isset($field['show_filter']) ? $field['show_filter'] : true,
                ];

                if (isset($field['choices'])) {
                    $filters['columns'][$fieldName]['options']['field_options']['choices'] = $field['choices'];
                }
            }
        }

        return [
            'filters' => $filters,
            'sorters' => $sorters
        ];
    }

    /**
     * @TODO fix adding actions from different scopes such as EXTEND
     *
     * @param array  $actions
     * @param string $type
     */
    protected function prepareRowActions(&$actions, $type)
    {
        $providers = $this->configManager->getProviders();
        foreach ($providers as $provider) {
            $gridActions = $provider->getPropertyConfig()->getGridActions($type);

            foreach ($gridActions as $config) {
                $configItem = [
                    'label' => $config['name'],
                    'icon'  => isset($config['icon']) ? $config['icon'] : 'question-sign',
                    'link'  => strtolower($config['name']) . '_link',
                    'type'  => isset($config['type']) ? $config['type'] : self::TYPE_NAVIGATE,
                ];

                if (isset($config['acl_resource'])) {
                    $configItem['acl_resource'] = $config['acl_resource'];
                }

                if (isset($config['defaultMessages'])) {
                    $configItem['defaultMessages'] = $config['defaultMessages'];
                }

                $actions = array_merge($actions, [strtolower($config['name']) => $configItem]);
            }
        }
    }

    /**
     * @param DatagridConfiguration $config
     * @param                       $itemType
     */
    protected function addEntityConfigProperties(DatagridConfiguration $config, $itemType)
    {
        // configure properties from config providers
        $properties = $config->offsetGetOr(Configuration::PROPERTIES_KEY, []);
        $columns    = $config->offsetGetByPath(self::PATH_COLUMNS, []);
        $actions    = [];

        $providers = $this->configManager->getProviders();
        foreach ($providers as $provider) {
            $gridActions = $provider->getPropertyConfig()->getGridActions($itemType);
            if (!empty($gridActions)) {
                $this->prepareProperties($gridActions, $columns, $properties, $actions);
            }
        }

        if (count($actions)) {
            $config->offsetSet(
                ActionExtension::ACTION_CONFIGURATION_KEY,
                $this->getActionConfigurationClosure($actions)
            );
        }
        $config->offsetSet(Configuration::PROPERTIES_KEY, $properties);
    }

    /**
     * @param array $gridActions
     * @param array $columns
     * @param array $properties
     * @param array $actions
     */
    protected function prepareProperties($gridActions, $columns, &$properties, &$actions)
    {
        foreach ($gridActions as $config) {
            $key = strtolower($config['name']);

            $properties[$key . '_link'] = [
                'type'   => 'url',
                'route'  => $config['route'],
                'params' => isset($config['args']) ? $config['args'] : []
            ];

            $filters = [];
            if (isset($config['filter'])) {
                foreach ($config['filter'] as $column => $filter) {
                    $dataName = isset($columns[$column]['data_name'])
                        ? $columns[$column]['data_name']
                        : $column;
                    $filters[$dataName] = $filter;
                }
            }
            $actions[$key] = $filters;
        }
    }

    /**
     * Returns closure that will configure actions for each row in grid
     *
     * @param array $actions
     *
     * @return callable
     */
    public function getActionConfigurationClosure($actions)
    {
        return function (ResultRecord $record) use ($actions) {
            $result = [];
            foreach ($actions as $action => $filters) {
                $isApplicable = true;
                foreach ($filters as $dataName => $filter) {
                    $value = $record->getValue($dataName);
                    if (is_array($filter)) {
                        $atLeastOneMatched = false;
                        foreach ($filter as $f) {
                            if ($value == $f) {
                                $atLeastOneMatched = true;
                                break;
                            }
                        }
                        if (!$atLeastOneMatched) {
                            $isApplicable = false;
                            break;
                        }
                    } elseif ($value != $filter) {
                        $isApplicable = false;
                        break;
                    }
                }
                $result[$action] = $isApplicable;
            }

            return $result;
        };
    }

    /**
     * @param QueryBuilder $query
     * @param string       $rootAlias
     * @param string       $joinAlias
     * @param string       $itemsType
     *
     * @return $this
     */
    protected function prepareQuery(QueryBuilder $query, $rootAlias, $joinAlias, $itemsType)
    {
        QueryBuilderUtil::checkIdentifier($rootAlias);
        QueryBuilderUtil::checkIdentifier($joinAlias);
        $providers = $this->configManager->getProviders();
        foreach ($providers as $provider) {
            $configItems = $provider->getPropertyConfig()->getItems($itemsType);
            foreach ($configItems as $code => $item) {
                QueryBuilderUtil::checkIdentifier($code);
                if (!isset($item['grid'])) {
                    continue;
                }
                if (!isset($item['options']['indexed']) || !$item['options']['indexed']) {
                    continue;
                }

                $alias     = $joinAlias . $provider->getScope() . '_' . $code;
                $fieldName = $provider->getScope() . '_' . $code;

                if (isset($item['grid']['query'])) {
                    $query->andWhere($alias . '.value ' . $item['grid']['query']['operator'] . ' :' . $alias);
                    $query->setParameter($alias, (string)$item['grid']['query']['value']);
                }

                $query->leftJoin(
                    $rootAlias . '.indexedValues',
                    $alias,
                    'WITH',
                    $alias . ".code='" . $code . "' AND " . $alias . ".scope='" . $provider->getScope() . "'"
                );
                $query->addSelect($alias . '.value as ' . $fieldName);
            }
        }

        return $this;
    }

    /**
     * @param array $gridConfig
     *
     * @return array
     */
    protected function mapEntityConfigTypes(array $gridConfig)
    {
        if (isset($gridConfig['type'])
            && $gridConfig['type'] === self::TYPE_HTML
            && isset($gridConfig['template'])
        ) {
            $gridConfig['type']          = self::TYPE_TWIG;
            $gridConfig['frontend_type'] = self::TYPE_HTML;
        } else {
            if (!empty($gridConfig['type'])) {
                $gridConfig['frontend_type'] = $gridConfig['type'];
            }

            $gridConfig['type'] = 'field';
        }

        return $gridConfig;
    }
}
