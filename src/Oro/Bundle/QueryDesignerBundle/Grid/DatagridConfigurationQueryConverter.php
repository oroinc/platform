<?php

namespace Oro\Bundle\QueryDesignerBundle\Grid;

use Doctrine\ORM\Query;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\GroupingOrmQueryConverter;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\SqlWalker;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class DatagridConfigurationQueryConverter extends GroupingOrmQueryConverter
{
    /**
     * @var DatagridGuesser
     */
    protected $datagridGuesser;

    /**
     * @var EntityNameResolver
     */
    protected $entityNameResolver;

    /**
     * @var DatagridConfiguration
     */
    protected $config;

    /**
     * @var array
     */
    protected $selectColumns;

    /**
     * @var array
     */
    protected $groupingColumns;

    /**
     * @var array
     */
    protected $from;

    /**
     * @var array
     */
    protected $innerJoins;

    /**
     * @var array
     */
    protected $leftJoins;

    /**
     * Constructor
     *
     * @param FunctionProviderInterface     $functionProvider
     * @param VirtualFieldProviderInterface $virtualFieldProvider
     * @param ManagerRegistry               $doctrine
     * @param DatagridGuesser               $datagridGuesser
     * @param EntityNameResolver            $entityNameResolver
     */
    public function __construct(
        FunctionProviderInterface $functionProvider,
        VirtualFieldProviderInterface $virtualFieldProvider,
        ManagerRegistry $doctrine,
        DatagridGuesser $datagridGuesser,
        EntityNameResolver $entityNameResolver
    ) {
        parent::__construct($functionProvider, $virtualFieldProvider, $doctrine);
        $this->datagridGuesser = $datagridGuesser;
        $this->entityNameResolver = $entityNameResolver;
    }

    /**
     * Converts a query specified in $source parameter to a datagrid configuration
     *
     * @param string                $gridName
     * @param AbstractQueryDesigner $source
     * @return DatagridConfiguration
     */
    public function convert($gridName, AbstractQueryDesigner $source)
    {
        $this->config = DatagridConfiguration::createNamed($gridName, []);
        $this->doConvert($source);

        return $this->config;
    }

    /**
     * {@inheritdoc}
     */
    protected function doConvert(AbstractQueryDesigner $source)
    {
        $this->selectColumns   = [];
        $this->groupingColumns = [];
        $this->from            = [];
        $this->innerJoins      = [];
        $this->leftJoins       = [];
        parent::doConvert($source);
        $this->selectColumns   = null;
        $this->groupingColumns = null;
        $this->from            = null;
        $this->innerJoins      = null;
        $this->leftJoins       = null;

        $this->config->setDatasourceType(OrmDatasource::TYPE);
        $this->config->getOrmQuery()->addHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SqlWalker::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function saveTableAliases($tableAliases)
    {
        $this->config->offsetSetByPath(QueryDesignerQueryConfiguration::TABLE_ALIASES, $tableAliases);
    }

    /**
     * {@inheritdoc}
     */
    protected function saveColumnAliases($columnAliases)
    {
        $this->config->offsetSetByPath(QueryDesignerQueryConfiguration::COLUMN_ALIASES, $columnAliases);
    }

    /**
     * {@inheritdoc}
     */
    protected function addSelectStatement()
    {
        parent::addSelectStatement();
        $this->config->getOrmQuery()->setSelect($this->selectColumns);
    }

    /**
     * {@inheritdoc}

     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function addSelectColumn(
        $entityClassName,
        $tableAlias,
        $fieldName,
        $columnExpr,
        $columnAlias,
        $columnLabel,
        $functionExpr,
        $functionReturnType,
        $isDistinct = false
    ) {
        if ($isDistinct) {
            $columnExpr = 'DISTINCT ' . $columnExpr;
        }

        if ($functionExpr !== null) {
            $functionExpr = $this->prepareFunctionExpression(
                $functionExpr,
                $tableAlias,
                $fieldName,
                $columnExpr,
                $columnAlias
            );
        }

        $fieldType = $functionReturnType;
        if ($fieldType === null) {
            $fieldType = $this->getFieldType($entityClassName, $fieldName);
        }

        if (!$functionExpr && $fieldType === 'dictionary') {
            list($entityAlias) = explode('.', $columnExpr);
            $nameDql = $this->entityNameResolver->getNameDQL(
                $this->getTargetEntityClass($entityClassName, $fieldName),
                $entityAlias
            );
            if ($nameDql) {
                $columnExpr = $nameDql;
            }
        }

        $this->selectColumns[] = sprintf(
            '%s as %s',
            $functionExpr !== null ? $functionExpr : $columnExpr,
            $columnAlias
        );

        $columnOptions = [
            DatagridGuesser::FORMATTER => [
                'label'        => $columnLabel,
                'translatable' => false
            ],
            DatagridGuesser::SORTER    => [
                'data_name' => $columnAlias
            ],
            DatagridGuesser::FILTER    => [
                'data_name'    => $this->getFilterByExpr($entityClassName, $tableAlias, $fieldName, $columnAlias),
                'translatable' => false
            ],
        ];
        if ($functionExpr !== null) {
            $columnOptions[DatagridGuesser::FILTER][FilterUtility::BY_HAVING_KEY] = true;
        }
        $this->datagridGuesser->applyColumnGuesses($entityClassName, $fieldName, $fieldType, $columnOptions);
        $this->datagridGuesser->setColumnOptions($this->config, $columnAlias, $columnOptions);

        $this->config->offsetSetByPath(
            sprintf('[fields_acl][columns][%s][data_name]', $columnAlias),
            $columnExpr
        );
    }

    /**
     * Get target entity class for virtual field.
     *
     * @param string $entityClassName
     * @param string $fieldName
     * @return string
     */
    protected function getTargetEntityClass($entityClassName, $fieldName)
    {
        if ($this->virtualFieldProvider->isVirtualField($entityClassName, $fieldName)) {
            $key = sprintf('%s::%s', $entityClassName, $fieldName);
            if (isset($this->virtualColumnOptions[$key]['related_entity_name'])) {
                return $this->virtualColumnOptions[$key]['related_entity_name'];
            }
        }
        return $entityClassName;
    }

    /**
     * {@inheritdoc}
     */
    protected function addFromStatements()
    {
        parent::addFromStatements();
        $this->config->getOrmQuery()->setFrom($this->from);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFromStatement($entityClassName, $tableAlias)
    {
        $this->from[] = [
            'table' => $entityClassName,
            'alias' => $tableAlias
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function addJoinStatements()
    {
        parent::addJoinStatements();
        if (!empty($this->innerJoins)) {
            $this->config->getOrmQuery()->setInnerJoins($this->innerJoins);
        }
        if (!empty($this->leftJoins)) {
            $this->config->getOrmQuery()->setLeftJoins($this->leftJoins);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addJoinStatement($joinType, $join, $joinAlias, $joinConditionType, $joinCondition)
    {
        $joinDefinition = [
            'join'  => $join,
            'alias' => $joinAlias
        ];
        if (!empty($joinConditionType)) {
            $joinDefinition['conditionType'] = $joinConditionType;
        }
        if (!empty($joinCondition)) {
            $joinDefinition['condition'] = $joinCondition;
        }

        if (self::LEFT_JOIN === $joinType) {
            $this->leftJoins[] = $joinDefinition;
        } else {
            $this->innerJoins[] = $joinDefinition;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addWhereStatement()
    {
        parent::addWhereStatement();
        if (!empty($this->filters)) {
            $this->config->offsetSetByPath(QueryDesignerQueryConfiguration::FILTERS, $this->filters);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addGroupByStatement()
    {
        parent::addGroupByStatement();
        if (!empty($this->groupingColumns)) {
            $this->config->getOrmQuery()->setGroupBy(implode(', ', $this->groupingColumns));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addGroupByColumn($columnAlias)
    {
        $this->groupingColumns[] = $columnAlias;
    }

    /**
     * {@inheritdoc}
     */
    protected function addOrderByColumn($columnAlias, $columnSorting)
    {
        $this->config->offsetSetByPath(
            sprintf('[sorters][default][%s]', $columnAlias),
            $columnSorting
        );
    }
}
