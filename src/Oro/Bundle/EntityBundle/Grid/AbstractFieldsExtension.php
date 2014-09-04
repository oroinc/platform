<?php

namespace Oro\Bundle\EntityBundle\Grid;

use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration as FormatterConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface as Property;
use Oro\Bundle\DataGridBundle\Extension\Sorter\Configuration as SorterConfiguration;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\FilterBundle\Grid\Extension\Configuration as FilterConfiguration;

abstract class AbstractFieldsExtension extends AbstractExtension
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /**
     * @param ConfigManager       $configManager
     * @param EntityClassResolver $entityClassResolver
     */
    public function __construct(ConfigManager $configManager, EntityClassResolver $entityClassResolver)
    {
        $this->configManager       = $configManager;
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $config->offsetGetByPath(Builder::DATASOURCE_TYPE_PATH) == OrmDatasource::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $fields = $this->getFields($config);
        foreach ($fields as $field) {
            $fieldName = $field->getFieldName();
            $column    = [
                'label' => $this->getFieldConfig('entity', $field)->get('label') ? : $fieldName
            ];
            $sorter    = [
                'data_name' => $fieldName
            ];
            $filter    = [
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
                sprintf('%s[%s]', SorterConfiguration::COLUMNS_PATH, $fieldName),
                $sorter
            );
            $config->offsetSetByPath(
                sprintf('%s[%s]', FilterConfiguration::COLUMNS_PATH, $fieldName),
                $filter
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $fields = $this->getFields($config);
        if (empty($fields)) {
            return;
        }

        $entityClassName = $this->entityClassResolver->getEntityClass($this->getEntityName($config));

        /** @var QueryBuilder $qb */
        $qb        = $datasource->getQueryBuilder();
        $fromParts = $qb->getDQLPart('from');
        $alias     = false;

        /** @var From $fromPart */
        foreach ($fromParts as $fromPart) {
            if ($this->entityClassResolver->getEntityClass($fromPart->getFrom()) == $entityClassName) {
                $alias = $fromPart->getAlias();
            }
        }

        if ($alias === false) {
            // add entity if it not exists in from clause
            $alias = 'o';
            $qb->from($entityClassName, $alias);
        }

        $relationIndex    = 0;
        $relationTemplate = 'auto_rel_%d';
        foreach ($fields as $field) {
            $fieldName = $field->getFieldName();
            switch ($field->getFieldType()) {
                case 'enum':
                    $extendFieldConfig = $this->getFieldConfig('extend', $field);
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
                sprintf('%s[%s][data_name]', SorterConfiguration::COLUMNS_PATH, $fieldName),
                $sorterDataName
            );
            $config->offsetSetByPath(
                sprintf('%s[%s][data_name]', FilterConfiguration::COLUMNS_PATH, $fieldName),
                sprintf('%s.%s', $alias, $fieldName)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 250;
    }

    /**
     * Gets a root entity name for which additional fields to be shown
     *
     * @param DatagridConfiguration $config
     *
     * @return string Entity class name
     */
    abstract protected function getEntityName(DatagridConfiguration $config);

    /**
     * Gets a list of fields to show
     *
     * @param DatagridConfiguration $config
     *
     * @return FieldConfigId[]
     */
    abstract protected function getFields(DatagridConfiguration $config);

    /**
     * Gets the full class name for the given entity name
     *
     * @param string $entityName
     *
     * @return string
     */
    protected function prepareEntityName($entityName)
    {
        return $this->configManager->getEntityManager()
            ->getClassMetadata($entityName)
            ->getName();
    }

    /**
     * @param FieldConfigId $field
     * @param array         $column
     * @param array         $sorter
     * @param array         $filter
     */
    protected function prepareFieldConfigs(FieldConfigId $field, array &$column, array &$sorter, array &$filter)
    {
        switch ($field->getFieldType()) {
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
                $filter['type']          = 'boolean';
                break;
            case 'date':
                $column['frontend_type'] = Property::TYPE_DATE;
                $filter['type']          = 'date';
                break;
            case 'datetime':
                $column['frontend_type'] = Property::TYPE_DATETIME;
                $filter['type']          = 'datetime';
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
                $extendFieldConfig = $this->getFieldConfig('extend', $field);

                $column['frontend_type']    = Property::TYPE_STRING;
                $filter['type']             = 'enum';
                $filter['null_value']       = ':empty:';
                $filter['options']['class'] = $extendFieldConfig->get('target_entity');
                break;
            case 'multiEnum':
                $extendFieldConfig = $this->getFieldConfig('extend', $field);

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
     * @param string        $scope
     * @param FieldConfigId $fieldId
     *
     * @return ConfigInterface
     */
    protected function getFieldConfig($scope, FieldConfigId $fieldId)
    {
        return $this->configManager->getProvider($scope)
            ->getConfig($fieldId->getClassName(), $fieldId->getFieldName());
    }
}
