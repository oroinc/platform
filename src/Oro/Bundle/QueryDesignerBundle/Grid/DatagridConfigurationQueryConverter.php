<?php

namespace Oro\Bundle\QueryDesignerBundle\Grid;

use Doctrine\ORM\Query;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\GroupingOrmQueryConverter;

class DatagridConfigurationQueryConverter extends GroupingOrmQueryConverter
{
    /**
     * @var DatagridGuesser
     */
    protected $datagridGuesser;

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
     */
    public function __construct(
        FunctionProviderInterface $functionProvider,
        VirtualFieldProviderInterface $virtualFieldProvider,
        ManagerRegistry $doctrine,
        DatagridGuesser $datagridGuesser
    ) {
        parent::__construct($functionProvider, $virtualFieldProvider, $doctrine);
        $this->datagridGuesser = $datagridGuesser;
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

        $this->config->offsetSetByPath('[source][type]', 'orm');
        $this->config->offsetSetByPath(
            '[source][hints]',
            [
                [
                    'name'  => Query::HINT_CUSTOM_OUTPUT_WALKER,
                    'value' => 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function saveTableAliases($tableAliases)
    {
        $this->config->offsetSetByPath('[source][query_config][table_aliases]', $tableAliases);
    }

    /**
     * {@inheritdoc}
     */
    protected function saveColumnAliases($columnAliases)
    {
        $this->config->offsetSetByPath('[source][query_config][column_aliases]', $columnAliases);
    }

    /**
     * {@inheritdoc}
     */
    protected function addSelectStatement()
    {
        parent::addSelectStatement();
        $this->config->offsetSetByPath('[source][query][select]', $this->selectColumns);
    }

    /**
     * {@inheritdoc}
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
        $this->selectColumns[] = sprintf(
            '%s as %s',
            $functionExpr !== null ? $functionExpr : $columnExpr,
            $columnAlias
        );

        $fieldType = $functionReturnType;
        if ($fieldType === null) {
            $fieldType = $this->getFieldType($entityClassName, $fieldName);
        }

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
    }

    /**
     * {@inheritdoc}
     */
    protected function addFromStatements()
    {
        parent::addFromStatements();
        $this->config->offsetSetByPath('[source][query][from]', $this->from);
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
            $this->config->offsetSetByPath('[source][query][join][inner]', $this->innerJoins);
        }
        if (!empty($this->leftJoins)) {
            $this->config->offsetSetByPath('[source][query][join][left]', $this->leftJoins);
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
            $this->config->offsetSetByPath('[source][query_config][filters]', $this->filters);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addGroupByStatement()
    {
        parent::addGroupByStatement();
        if (!empty($this->groupingColumns)) {
            $this->config->offsetSetByPath('[source][query][groupBy]', implode(', ', $this->groupingColumns));
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
