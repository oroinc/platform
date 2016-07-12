<?php

namespace Oro\Bundle\EntityExtendBundle\Grid;

use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration as FormatterConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Sorter\Configuration as SorterConfiguration;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FilterBundle\Grid\Extension\Configuration as FilterConfiguration;
use Oro\Bundle\DataGridBundle\Extension\FieldAcl\Configuration as FieldAclConfiguration;

abstract class AbstractFieldsExtension extends AbstractExtension
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var DatagridGuesser */
    protected $datagridGuesser;

    /**
     * @param ConfigManager       $configManager
     * @param EntityClassResolver $entityClassResolver
     * @param DatagridGuesser     $datagridGuesser
     */
    public function __construct(
        ConfigManager $configManager,
        EntityClassResolver $entityClassResolver,
        DatagridGuesser $datagridGuesser
    ) {
        $this->configManager       = $configManager;
        $this->entityClassResolver = $entityClassResolver;
        $this->datagridGuesser     = $datagridGuesser;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $config->getDatasourceType() == OrmDatasource::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $fields = $this->getFields($config);
        foreach ($fields as $field) {
            $columnOptions = [];
            $this->prepareColumnOptions($field, $columnOptions);
            $this->datagridGuesser->setColumnOptions($config, $field->getFieldName(), $columnOptions);
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
                    $selectExpr     = sprintf('IDENTITY(%s.%s) as %s', $alias, $fieldName, $fieldName);
                    break;
                case 'multiEnum':
                    $columnDataName = ExtendHelper::getMultiEnumSnapshotFieldName($fieldName);
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

            // add Field ACL configuration
            $config->offsetSetByPath(
                sprintf('%s[%s][data_name]', FieldAclConfiguration::COLUMNS_PATH, $columnDataName),
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
     * @param FieldConfigId $field
     * @param array         $columnOptions
     */
    protected function prepareColumnOptions(FieldConfigId $field, array &$columnOptions)
    {
        $fieldName = $field->getFieldName();

        // if field is "visible as mandatory" it is required in grid settings and rendered
        // if field is just "visible" it's rendered by default and manageable in grid settings
        // otherwise - not required and hidden by default.
        $gridVisibilityValue = (int)$this->getFieldConfig('datagrid', $field)->get('is_visible');

        $isRequired   = $gridVisibilityValue === DatagridScope::IS_VISIBLE_MANDATORY;
        $isRenderable = $isRequired ? : $gridVisibilityValue === DatagridScope::IS_VISIBLE_TRUE;

        $columnOptions = [
            DatagridGuesser::FORMATTER => [
                'label'      => $this->getFieldConfig('entity', $field)->get('label') ? : $fieldName,
                'renderable' => $isRenderable,
                'required'   => $isRequired
            ],
            DatagridGuesser::SORTER    => [
                'data_name' => $fieldName
            ],
            DatagridGuesser::FILTER    => [
                'data_name' => $fieldName,
                'enabled'   => false
            ],
        ];

        $this->datagridGuesser->applyColumnGuesses(
            $field->getClassName(),
            $fieldName,
            $field->getFieldType(),
            $columnOptions
        );
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
