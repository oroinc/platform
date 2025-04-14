<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Date grouping filter.
 *
 * Optimized filter that uses simple queries to filter by date.
 */
class EntityDateGroupingFilter extends ChoiceFilter
{
    public const NAME = 'entity_date_grouping';

    private const TYPE_YEAR = 'year';
    private const TYPE_QUARTER = 'quarter';
    private const TYPE_MONTH = 'month';
    private const TYPE_DAY = 'day';

    private const COLUMN_YEAR = 'column_year';
    private const COLUMN_QUARTER = 'column_quarter';
    private const COLUMN_MONTH = 'column_month';
    private const COLUMN_DAY = 'column_day';

    private const COLUMN_NAME = 'column_name';

    // Do not change the order as it is taken into account during sorting.
    private const SORTERS = [self::COLUMN_YEAR, self::COLUMN_QUARTER, self::COLUMN_MONTH, self::COLUMN_DAY];

    private const FILTERS = [
        self::TYPE_DAY => [self::COLUMN_YEAR, self::COLUMN_MONTH, self::COLUMN_DAY],
        self::TYPE_MONTH => [self::COLUMN_YEAR, self::COLUMN_MONTH],
        self::TYPE_QUARTER => [self::COLUMN_YEAR, self::COLUMN_QUARTER],
        self::TYPE_YEAR => [self::COLUMN_YEAR],
    ];

    public function __construct(FormFactoryInterface $factory, FilterUtility $util)
    {
        parent::__construct($factory, $util);
    }

    /**
     * {@inheritDoc}
     */
    public function init($name, array $params): void
    {
        $params[FilterUtility::FRONTEND_TYPE_KEY] = 'select';
        parent::init($name, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data): bool
    {
        if (!$data) {
            return false;
        }

        $this->applyFilter($ds->getQueryBuilder(), self::FILTERS[$data['value']]);

        return true;
    }

    public function applyOrderBy(OrmDatasource $datasource, string $sortKey, string $direction): void
    {
        QueryBuilderUtil::checkField($sortKey);
        $direction = QueryBuilderUtil::getSortOrder($direction);

        $qb = $datasource->getQueryBuilder();
        $columnName = $this->get(self::COLUMN_NAME);

        // Get the select expression by alias.
        $select = QueryBuilderUtil::getSelectExprByAlias($qb, $columnName);

        // Loop through the predefined columns and add ordering if the column is present in the select.
        foreach (self::SORTERS as $columnName) {
            $columnName = $this->get($columnName);

            // Check if column is used in the select expression and add ordering.
            if (str_contains($select, $columnName)) {
                QueryBuilderUtil::checkField($columnName);
                $qb->addOrderBy($columnName, $direction);
            }
        }
    }

    private function applyFilter(QueryBuilder $qb, array $columns): void
    {
        // Get the columns from the grid configuration.
        $selectAndGroupByColumns = array_map(fn ($column) => $this->get($column), $columns);

        // Validate each column name to avoid SQL injection.
        array_walk($selectAndGroupByColumns, fn ($column) => QueryBuilderUtil::checkField($column));

        $this->buildSelect($qb, $selectAndGroupByColumns);
        $qb->addGroupBy(...$selectAndGroupByColumns);
    }

    private function buildSelect(QueryBuilder $qb, array $columns): void
    {
        $alias = $this->get(self::COLUMN_NAME);
        QueryBuilderUtil::checkField($alias);
        if (count($columns) > 1) {
            // array_reverse to generate a date in d-m-Y format.
            $selectExpr = sprintf('CONCAT(%s) AS %s', implode(', \'-\', ', array_reverse($columns)), $alias);
        } else {
            $selectExpr = sprintf('%s AS %s', $columns[0], $alias);
        }

        $qb->addSelect($selectExpr);
    }
}
