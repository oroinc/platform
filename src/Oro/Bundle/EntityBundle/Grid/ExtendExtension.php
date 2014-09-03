<?php

namespace Oro\Bundle\EntityBundle\Grid;

use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration as FormatterConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Sorter\Configuration as OrmSorterConfiguration;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface as Property;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\FilterBundle\Grid\Extension\Configuration as FilterConfiguration;

class ExtendExtension extends AbstractExtension
{
    const EXTEND_ENTITY_CONFIG_PATH = '[extended_entity_name]';

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $config->offsetGetByPath(self::EXTEND_ENTITY_CONFIG_PATH, false) !== false;
    }

    /**
     * {@inheritDoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $entityName = $this->getExtendedEntityNameByConfig($config);
        $fields     = $this->getDynamicFields($entityName);
        foreach ($fields as $field) {
            $fieldName   = $field->getFieldName();
            $fieldConfig = $this->getFieldConfig('entity', $field->getClassName(), $fieldName);
            $column      = [
                'label' => $fieldConfig->get('label') ? : $fieldName
            ];
            $sorter      = [
                'data_name' => $fieldName
            ];
            $filter      = [
                'data_name' => $fieldName,
                'enabled'   => false,
                'options'   => []
            ];
            $this->prepareFieldConfigs($field, $column, $sorter, $filter);

            $config->offsetSetByPath(
                sprintf('[%s][%s]', FormatterConfiguration::COLUMNS_KEY, $fieldName),
                $column
            );
            $config->offsetSetByPath(
                sprintf('%s[%s]', OrmSorterConfiguration::COLUMNS_PATH, $fieldName),
                $sorter
            );
            $config->offsetSetByPath(
                sprintf('%s[%s]', FilterConfiguration::COLUMNS_PATH, $fieldName),
                $filter
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        if (!($datasource instanceof OrmDatasource)) {
            return;
        }

        $entityName = $this->getExtendedEntityNameByConfig($config);
        $fields     = $this->getDynamicFields($entityName);
        if (empty($fields)) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb        = $datasource->getQueryBuilder();
        $fromParts = $qb->getDQLPart('from');
        $alias     = false;

        /** @var From $fromPart */
        foreach ($fromParts as $fromPart) {
            if ($this->prepareEntityName($fromPart->getFrom()) == $entityName) {
                $alias = $fromPart->getAlias();
            }
        }

        if ($alias === false) {
            // add entity if it not exists in from clause
            $alias = 'o';
            $qb->from($entityName, $alias);
        }

        $relationIndex    = 0;
        $relationTemplate = 'auto_rel_%d';
        foreach ($fields as $field) {
            $fieldName = $field->getFieldName();
            $fieldType = $field->getFieldType();

            switch ($fieldType) {
                case 'enum':
                    $extendFieldConfig = $this->getFieldConfig('extend', $field->getClassName(), $fieldName);
                    $joinAlias         = sprintf($relationTemplate, ++$relationIndex);
                    $qb->leftJoin(sprintf('%s.%s', $alias, $fieldName), $joinAlias);
                    $columnDataName = $fieldName;
                    $sorterDataName = sprintf('%s.%s', $joinAlias, $extendFieldConfig->get('target_field'));
                    $selectExpr     = sprintf('%s as %s', $sorterDataName, $fieldName);
                    break;
                case 'multiEnum':
                    $columnDataName = ExtendHelper::getMultipleEnumSnapshotFieldName($fieldName);
                    $sorterDataName = sprintf('%s.%s', $alias, $columnDataName);
                    $selectExpr     = $sorterDataName;
                    break;
                default:
                    $columnDataName = $fieldName;
                    $sorterDataName = sprintf('%s.%s', $alias, $fieldName);
                    $selectExpr     = $sorterDataName;
                    break;
            }

            $qb->addSelect($selectExpr);

            // set real "data name" for filters and sorters
            $config->offsetSetByPath(
                sprintf('[%s][%s][data_name]', FormatterConfiguration::COLUMNS_KEY, $fieldName),
                $columnDataName
            );
            $config->offsetSetByPath(
                sprintf('%s[%s][data_name]', OrmSorterConfiguration::COLUMNS_PATH, $fieldName),
                $sorterDataName
            );
            $config->offsetSetByPath(
                sprintf('%s[%s][data_name]', FilterConfiguration::COLUMNS_PATH, $fieldName),
                sprintf('%s.%s', $alias, $fieldName)
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPriority()
    {
        return 250;
    }

    /**
     * Convert entityName to the full format
     *
     * @param  string $entityName
     * @return string
     */
    protected function prepareEntityName($entityName)
    {
        return $this->configManager->getEntityManager()->getClassMetadata($entityName)->getName();
    }

    /**
     * @param DatagridConfiguration $config
     * @throws \Exception when class was not found by $entityName
     * @return string extended entity class name
     */
    protected function getExtendedEntityNameByConfig(DatagridConfiguration $config)
    {
        return $this->prepareEntityName($config->offsetGetByPath(self::EXTEND_ENTITY_CONFIG_PATH));
    }

    /**
     * Get list of dynamic fields to show
     *
     * @param string $entityName
     *
     * @return FieldConfigId[]
     */
    protected function getDynamicFields($entityName)
    {
        $fields = [];
        if ($this->configManager->hasConfig($entityName)) {
            $entityConfigProvider   = $this->configManager->getProvider('entity');
            $extendConfigProvider   = $this->configManager->getProvider('extend');
            $datagridConfigProvider = $this->configManager->getProvider('datagrid');

            $fieldIds = $entityConfigProvider->getIds($entityName);
            foreach ($fieldIds as $fieldId) {
                $extendConfig = $extendConfigProvider->getConfigById($fieldId);
                if ($extendConfig->is('owner', ExtendScope::OWNER_CUSTOM)
                    && $datagridConfigProvider->getConfigById($fieldId)->is('is_visible')
                    && !$extendConfig->is('state', ExtendScope::STATE_NEW)
                    && !$extendConfig->is('is_deleted')
                ) {
                    $fields[] = $fieldId;
                }
            }
        }

        return $fields;
    }

    /**
     * @param FieldConfigId $field
     * @param array         $column
     * @param array         $sorter
     * @param array         $filter
     */
    protected function prepareFieldConfigs(
        FieldConfigId $field,
        array &$column,
        array &$sorter,
        array &$filter
    ) {
        $fieldName = $field->getFieldName();
        $fieldType = $field->getFieldType();
        switch ($fieldType) {
            case 'integer':
            case 'smallint':
            case 'bigint':
                $column['frontend_type'] = Property::TYPE_INTEGER;
                $filter['type']          = 'number';
                break;
            case 'decimal':
            case 'float':
                $column['frontend_type']        = Property::TYPE_DECIMAL;
                $filter['type']                 = 'number';
                $filter['options']['data_type'] = NumberFilterType::DATA_DECIMAL;
                break;
            case 'boolean':
                $column['frontend_type'] = Property::TYPE_BOOLEAN;
                break;
            case 'date':
                $column['frontend_type'] = Property::TYPE_DATE;
                break;
            case 'datetime':
                $column['frontend_type'] = Property::TYPE_DATETIME;
                break;
            case 'money':
                $column['frontend_type'] = Property::TYPE_CURRENCY;
                $filter['type']          = 'number';
                break;
            case 'percent':
                $column['frontend_type'] = Property::TYPE_PERCENT;
                $filter['type']          = 'percent';
                break;
            case 'enum':
                $extendFieldConfig = $this->getFieldConfig('extend', $field->getClassName(), $fieldName);

                $column['frontend_type']    = Property::TYPE_STRING;
                $filter['type']             = 'enum';
                $filter['null_value']       = ':empty:';
                $filter['options']['class'] = $extendFieldConfig->get('target_entity');
                break;
            case 'multiEnum':
                $extendFieldConfig = $this->getFieldConfig('extend', $field->getClassName(), $fieldName);

                $column['frontend_type']           = Property::TYPE_HTML;
                $column['type']                    = 'twig';
                $column['template']                = 'OroEntityExtendBundle:Datagrid:Property/multiEnum.html.twig';
                $column['context']['entity_class'] = $extendFieldConfig->get('target_entity');
                $filter['type']                    = 'multi_enum';
                $filter['null_value']              = ':empty:';
                $filter['options']['class']        = $extendFieldConfig->get('target_entity');
                break;
            default:
                $column['frontend_type'] = Property::TYPE_STRING;
                $filter['type']          = Property::TYPE_STRING;
                break;
        }
    }

    /**
     * @param string $scope
     * @param string $className
     * @param string $fieldName
     *
     * @return ConfigInterface
     */
    protected function getFieldConfig($scope, $className, $fieldName)
    {
        return $this->configManager->getProvider($scope)
            ->getConfig($className, $fieldName);
    }
}
