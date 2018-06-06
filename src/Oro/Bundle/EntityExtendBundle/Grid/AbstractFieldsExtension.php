<?php

namespace Oro\Bundle\EntityExtendBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\FieldAcl\Configuration as FieldAclConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration as FormatterConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Sorter\Configuration as SorterConfiguration;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FilterBundle\Grid\Extension\Configuration as FilterConfiguration;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

abstract class AbstractFieldsExtension extends AbstractExtension
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var DatagridGuesser */
    protected $datagridGuesser;

    /** @var FieldsHelper */
    protected $fieldsHelper;

    /**
     * @param ConfigManager       $configManager
     * @param EntityClassResolver $entityClassResolver
     * @param DatagridGuesser     $datagridGuesser
     * @param FieldsHelper        $fieldsHelper
     */
    public function __construct(
        ConfigManager $configManager,
        EntityClassResolver $entityClassResolver,
        DatagridGuesser $datagridGuesser,
        FieldsHelper $fieldsHelper
    ) {
        $this->configManager       = $configManager;
        $this->entityClassResolver = $entityClassResolver;
        $this->datagridGuesser     = $datagridGuesser;
        $this->fieldsHelper = $fieldsHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            parent::isApplicable($config)
            && $config->isOrmDatasource();
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $fields = $this->getFields($config);
        if (empty($fields)) {
            return;
        }

        foreach ($fields as $field) {
            $fieldName = $field->getFieldName();
            $columnOptions =
                [
                    DatagridGuesser::FORMATTER => $config->offsetGetByPath(
                        sprintf('[%s][%s]', FormatterConfiguration::COLUMNS_KEY, $fieldName),
                        []
                    ),
                    DatagridGuesser::SORTER => $config->offsetGetByPath(
                        sprintf('%s[%s]', SorterConfiguration::COLUMNS_PATH, $fieldName),
                        []
                    ),
                    DatagridGuesser::FILTER => $config->offsetGetByPath(
                        sprintf('%s[%s]', FilterConfiguration::COLUMNS_PATH, $fieldName),
                        []
                    ),
                ];
            $this->prepareColumnOptions($field, $columnOptions);
            $this->datagridGuesser->setColumnOptions($config, $field->getFieldName(), $columnOptions);
        }

        $entityClassName = $this->entityClassResolver->getEntityClass($this->getEntityName($config));
        $alias = $config->getOrmQuery()->findRootAlias($entityClassName, $this->entityClassResolver);
        if (!$alias) {
            $alias = 'o';
            $config->getOrmQuery()->addFrom($entityClassName, $alias);
        }

        $this->buildExpression($fields, $config, $alias);
    }

    /**
     * @param FieldConfigId[] $fields
     * @param DatagridConfiguration $config
     * @param string $alias
     */
    public function buildExpression(array $fields, DatagridConfiguration $config, $alias)
    {
        $query = $config->getOrmQuery();
        foreach ($fields as $field) {
            $fieldName = $field->getFieldName();
            switch ($field->getFieldType()) {
                case 'enum':
                    $extendFieldConfig = $this->getFieldConfig('extend', $field);
                    $join = sprintf('%s.%s', $alias, $fieldName);
                    $joinAlias = $query->getJoinAlias($join);
                    $query->addLeftJoin($join, $joinAlias);
                    $columnDataName = $fieldName;

                    $targetField = $extendFieldConfig->get('target_field');
                    $sorterDataName = sprintf('%s_%s', $joinAlias, $targetField);
                    $sorterSelectExpr = sprintf('%s.%s as %s', $joinAlias, $targetField, $sorterDataName);

                    $selectExpr = sprintf('IDENTITY(%s.%s) as %s', $alias, $fieldName, $fieldName);
                    $filterDataName = sprintf('%s.%s', $alias, $fieldName);
                    // adding $filterDataName to select list to allow sorting by this column and avoid GROUP BY error
                    $selectExpr = [$selectExpr, $sorterSelectExpr];
                    break;
                case 'multiEnum':
                    $columnDataName = ExtendHelper::getMultiEnumSnapshotFieldName($fieldName);
                    $sorterDataName = sprintf('%s.%s', $alias, $columnDataName);
                    $filterDataName = sprintf('%s.%s', $alias, $fieldName);
                    $selectExpr = $sorterDataName;
                    break;
                case RelationType::MANY_TO_ONE:
                case RelationType::ONE_TO_ONE:
                    $extendFieldConfig = $this->getFieldConfig('extend', $field);
                    $join = sprintf('%s.%s', $alias, $fieldName);
                    $joinAlias = $query->getJoinAlias($join);
                    $query->addLeftJoin($join, $joinAlias);

                    $dataName = $fieldName . '_target_field';
                    $targetField = $extendFieldConfig->get('target_field', false, 'id');
                    $dataFieldName = sprintf('%s.%s', $joinAlias, $targetField);

                    $groupBy = $query->getGroupBy();
                    if ($groupBy) {
                        $query->addGroupBy($dataFieldName);
                    }

                    $selectExpr = sprintf('%s as %s', $dataFieldName, $dataName);
                    $query->addSelect(sprintf('IDENTITY(%s.%s) as %s_identity', $alias, $fieldName, $fieldName));

                    $columnDataName = $sorterDataName = $dataName;
                    $filterDataName = sprintf('IDENTITY(%s.%s)', $alias, $fieldName);
                    break;
                default:
                    $columnDataName = $fieldName;
                    $selectExpr = $sorterDataName = $filterDataName = sprintf('%s.%s', $alias, $fieldName);
                    break;
            }

            $query->addSelect($selectExpr);

            // set real "data name" for filters and sorters
            $config->offsetSetByPath(
                sprintf('[%s][%s][data_name]', FormatterConfiguration::COLUMNS_KEY, $fieldName),
                $columnDataName
            );

            $path = sprintf('%s[%s][data_name]', SorterConfiguration::COLUMNS_PATH, $fieldName);
            if ($fieldName === $config->offsetGetByPath($path, $fieldName)) {
                $config->offsetSetByPath($path, $sorterDataName);
            }
            $path = sprintf('%s[%s][data_name]', FilterConfiguration::COLUMNS_PATH, $fieldName);
            if ($fieldName === $config->offsetGetByPath($path, $fieldName)) {
                $config->offsetSetByPath($path, $filterDataName);
            }

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
    protected function getFields(DatagridConfiguration $config)
    {
        return $this->fieldsHelper->getFields($this->getEntityName($config));
    }

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

        $columnOptions = ArrayUtil::arrayMergeRecursiveDistinct(
            [
                DatagridGuesser::FORMATTER => [
                    'label' => $this->getFieldConfig('entity', $field)->get('label', false, $fieldName),
                    'renderable' => $isRenderable,
                    'required' => $isRequired,
                    'order' => $this->getFieldConfig('datagrid', $field)->get('order', false, 0),
                ],
                DatagridGuesser::SORTER => [
                    'data_name' => $fieldName,
                ],
                DatagridGuesser::FILTER => [
                    'data_name' => $fieldName,
                    'enabled' => false,
                ],
            ],
            $columnOptions
        );

        switch ($field->getFieldType()) {
            case RelationType::MANY_TO_ONE:
            case RelationType::ONE_TO_ONE:
            case RelationType::TO_ONE:
                $extendFieldConfig = $this->getFieldConfig('extend', $field);
                $columnOptions = ArrayUtil::arrayMergeRecursiveDistinct(
                    [
                        DatagridGuesser::FILTER => [
                            'type' => 'entity',
                            'translatable' => true,
                            'options' => [
                                'field_type' => EntityType::class,
                                'field_options' => [
                                    'class' => $extendFieldConfig->get('target_entity'),
                                    'choice_label' => $extendFieldConfig->get('target_field'),
                                    'multiple' => true,
                                ],
                            ],
                        ],
                    ],
                    $columnOptions
                );
                break;
            default:
                break;
        }

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
