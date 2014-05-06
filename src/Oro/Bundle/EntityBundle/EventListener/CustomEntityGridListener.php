<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Provider\SystemAwareResolver;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\EventListener\AbstractConfigGridListener;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class CustomEntityGridListener extends AbstractConfigGridListener
{
    const GRID_NAME = 'custom-entity-grid';
    const PATH_FROM = '[source][query][from]';

    /** @var ConfigManager */
    protected $configManager;

    /** @var null original entity class */
    protected $entityClass = null;

    /** @var  integer parent entity id */
    protected $parentId;

    /** @var Router */
    protected $router;

    /** @var Request */
    protected $request;

    protected $filterMap = array(
        'string'   => 'string',
        'integer'  => 'number',
        'smallint' => 'number',
        'bigint'   => 'number',
        'boolean'  => 'boolean',
        'decimal'  => 'number',
        'date'     => 'range',
        'text'     => 'string',
        'float'    => 'number',
        'money'    => 'number',
        'percent'  => 'percent'
    );

    protected $typeMap = array(
        'string'   => 'string',
        'integer'  => 'number',
        'smallint' => 'number',
        'bigint'   => 'number',
        'boolean'  => 'boolean',
        'decimal'  => 'decimal',
        'date'     => 'datetime',
        'text'     => 'string',
        'float'    => 'decimal',
        'money'    => 'number',
        'percent'  => 'percent'
    );

    /**
     * @var ParameterBag
     */
    protected $parameters;

    /**
     * @param ConfigManager       $configManager
     * @param SystemAwareResolver $datagridResolver
     * @param Router              $router
     */
    public function __construct(
        ConfigManager $configManager,
        SystemAwareResolver $datagridResolver,
        Router $router
    ) {
        parent::__construct($configManager, $datagridResolver);

        $this->router        = $router;
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $this->parameters = null;
    }

    /**
     * @param BuildBefore $event
     * @return bool
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $this->parameters = $event->getDatagrid()->getParameters();

        $entityClass = $this->getParam('class_name');
        if (empty($entityClass)) {
            $entityClass = $this->request->attributes->get('id');
        }

        if (empty($this->entityClass) && $entityClass !== false) {
            $this->entityClass = str_replace('_', '\\', $entityClass);
        }

        if (empty($this->entityClass)) {
            return false;
        }

        $config = $event->getConfig();

        // get dynamic columns
        $additionalColumnSettings = $this->getDynamicFields(
            $config->offsetGetByPath('[source][query][from][0][alias]', 'ce')
        );
        $filtersSorters           = $this->getDynamicSortersAndFilters($additionalColumnSettings);
        $additionalColumnSettings = array_merge(
            $additionalColumnSettings,
            [
                'sorters' => $filtersSorters['sorters'],
                'filters' => $filtersSorters['filters'],
            ]
        );

        foreach (['columns', 'sorters', 'filters', 'source'] as $itemName) {
            $path = '[' . $itemName . ']';

            // get already defined items
            $items = $config->offsetGetByPath($path, []);
            if (!empty($additionalColumnSettings[$itemName])) {
                $items = array_merge_recursive($items, $additionalColumnSettings[$itemName]);
            }

            // set new item set with dynamic columns/sorters/filters
            $config->offsetSetByPath($path, $items);
        }

        // set entity to select from
        $from    = $config->offsetGetByPath(self::PATH_FROM, []);
        $from[0] = array_merge($from[0], ['table' => $this->entityClass]);
        $config->offsetSetByPath('[source][query][from]', $from);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDynamicFields($alias = null, $itemsType = null)
    {
        $columns = $selects = [];

        /** @var ConfigProvider $extendConfigProvider */
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $extendConfigs        = $extendConfigProvider->getConfigs($this->entityClass);

        foreach ($extendConfigs as $extendConfig) {
            if (!$extendConfig->is('state', ExtendScope::STATE_NEW) && !$extendConfig->get('is_deleted')) {
                list($field, $selectField) = $this->getDynamicFieldItem($alias, $extendConfig);

                if (!empty($field)) {
                    $columns[] = $field;
                }

                if (!empty($selectField)) {
                    $selects[] = $selectField;
                }
            }
        }

        ksort($columns);

        $orderedColumns = $sorters = $filters = [];
        // compile field list with pre-defined order
        foreach ($columns as $field) {
            $orderedColumns = array_merge($orderedColumns, $field);
        }

        $result = [
            'columns' => $orderedColumns,
        ];

        if (!empty($selects)) {
            $result = array_merge(
                $result,
                [
                    'source' => [
                        'query' => ['select' => $selects],
                    ]
                ]
            );
        }

        return $result;
    }

    /**
     * Get dynamic field or empty array if field is not visible
     *
     * @param                 $alias
     * @param ConfigInterface $extendConfig
     * @return array
     */
    public function getDynamicFieldItem($alias, ConfigInterface $extendConfig)
    {
        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId = $extendConfig->getId();

        /** @var ConfigProvider $datagridProvider */
        $datagridConfigProvider = $this->configManager->getProvider('datagrid');
        $datagridConfig         = $datagridConfigProvider->getConfig(
            $this->entityClass,
            $fieldConfigId->getFieldName()
        );

        $select = '';
        $field  = [];
        if ($datagridConfig->is('is_visible')) {
            /** @var ConfigProvider $entityConfigProvider */
            $entityConfigProvider = $this->configManager->getProvider('entity');
            $entityConfig         = $entityConfigProvider->getConfig(
                $this->entityClass,
                $fieldConfigId->getFieldName()
            );

            $label     = $entityConfig->get('label') ? : $fieldConfigId->getFieldName();
            $fieldName = $fieldConfigId->getFieldName();

            $field  = $this->createFieldArrayDefinition($fieldName, $label, $fieldConfigId);
            $select = $alias . '.' . $fieldName;
        }

        return [$field, $select];
    }

    /**
     * @param string        $code
     * @param               $label
     * @param FieldConfigId $fieldConfigId
     *
     * @return array
     */
    protected function createFieldArrayDefinition($code, $label, FieldConfigId $fieldConfigId, $isVisible = true)
    {
        // TODO: getting a field type from a model here is a temporary solution.
        // We need to use $fieldConfigId->getFieldType()
        $fieldType = $this->configManager->getConfigFieldModel(
            $fieldConfigId->getClassName(),
            $fieldConfigId->getFieldName()
        )->getType();

        return [
            $code => [
                'type'          => 'field',
                'label'         => $label,
                'field_name'    => $code,
                'filter_type'   => $this->filterMap[$fieldType],
                'required'      => false,
                'sortable'      => true,
                'filterable'    => true,
                'show_filter'   => true,
                'frontend_type' => $this->getFrontendFieldType($fieldConfigId->getFieldType()),
                'renderable'    => $isVisible
            ]
        ];
    }

    /**
     * Gets a datagrid column frontend type for the given field type
     *
     * @param string $fieldType
     * @return string
     */
    protected function getFrontendFieldType($fieldType)
    {
        switch ($fieldType) {
            case 'integer':
            case 'smallint':
            case 'bigint':
                return PropertyInterface::TYPE_INTEGER;
            case 'decimal':
            case 'float':
                return PropertyInterface::TYPE_DECIMAL;
            case 'boolean':
                return PropertyInterface::TYPE_BOOLEAN;
            case 'date':
                return PropertyInterface::TYPE_DATE;
            case 'datetime':
                return PropertyInterface::TYPE_DATETIME;
            case 'money':
                return PropertyInterface::TYPE_CURRENCY;
            case 'percent':
                return PropertyInterface::TYPE_PERCENT;
        }

        return PropertyInterface::TYPE_STRING;
    }

    /**
     * @param string $gridName
     * @param string $keyName
     * @param array  $node
     *
     * @return callable
     */
    public function getLinkProperty($gridName, $keyName, $node)
    {
        $router = $this->router;
        if (!isset($node['route'])) {
            return false;
        } else {
            $route = $node['route'];
        }

        return function (ResultRecord $record) use ($router, $route) {
            $className = $this->getParam('class_name');
            return $router->generate(
                $route,
                array(
                    'entity_id' => str_replace('\\', '_', $className),
                    'id'        => $record->getValue('id')
                )
            );
        };
    }

    /**
     * Trying to get request param
     * - first from current request query
     * - then from master request attributes
     *
     * @param string $name
     * @param bool $default
     * @return mixed
     */
    protected function getParam($name, $default = false)
    {
        if (!$this->parameters) {
            throw new \BadMethodCallException('Method must be called only while datagrid is building.');
        }

        $paramValue = $this->parameters->get($name, $default);
        if ($paramValue === false) {
            $paramNameCamelCase = str_replace(
                ' ',
                '',
                lcfirst(
                    ucwords(str_replace('_', ' ', $name))
                )
            );

            $paramValue = $this->request->attributes->get($paramNameCamelCase, $default);
        }

        return $paramValue;
    }

    /**
     * @return Request
     */
    protected function getRequest()
    {
        return $this->request;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        if ($request instanceof Request) {
            $this->request = $request;
        }
    }
}
