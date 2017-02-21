<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateGroupingFilterType;

class DateGroupingFilter extends ChoiceFilter
{
    const NAME = 'date_grouping';
    const COLUMN_NAME_SUFFIX = 'DateGroupingFilter';
    const CALENDAR_TABLE = 'calendarDate';
    const CALENDAR_COLUMN = 'date';
    const FIRST_CALENDAR_TABLE_FOR_GROUPING = 'calendarDate1';
    const SECOND_CALENDAR_TABLE_FOR_GROUPING = 'calendarDate2';
    const CALENDAR_COLUMN_FOR_GROUPING = 'date';
    const FIRST_JOINED_TABLE = 'firstJoinedTableAlias';
    const SECOND_JOINED_TABLE = 'secondJoinedTableAlias';
    const TARGET_COLUMN = 'date';

    /** @var array */
    protected $groupingNames = [];

    /** @var EntityManager */
    protected $entityManager;

    /** @var array */
    protected $config = [];

    /**
     * @param EntityManager $entityManager
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return DateGroupingFilterType::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        $this->resolveConfiguration();

        /** @var OrmFilterDatasourceAdapter $ds */
        $qb = $ds->getQueryBuilder();
        $dataName = $this->generateDataName($this->get(FilterUtility::DATA_NAME_KEY) . self::COLUMN_NAME_SUFFIX);
        $columnName = $this->getOr('column_name', $dataName);

        switch ($data['value']) {
            case DateGroupingFilterType::TYPE_DAY:
                $this->addFilter(DateGroupingFilterType::TYPE_DAY, $qb);
                $this->addFilter(DateGroupingFilterType::TYPE_MONTH, $qb);
                $this->addFilter(DateGroupingFilterType::TYPE_YEAR, $qb);
                $qb->addSelect(
                    sprintf(
                        "CONCAT(%s, '-', %s, '-', %s) as $columnName",
                        $this->groupingNames[DateGroupingFilterType::TYPE_DAY],
                        $this->groupingNames[DateGroupingFilterType::TYPE_MONTH],
                        $this->groupingNames[DateGroupingFilterType::TYPE_YEAR]
                    )
                );
                break;
            case DateGroupingFilterType::TYPE_MONTH:
                $this->addFilter(DateGroupingFilterType::TYPE_MONTH, $qb);
                $this->addFilter(DateGroupingFilterType::TYPE_YEAR, $qb);
                $qb->addSelect(
                    sprintf(
                        "CONCAT(%s, '-', %s) as $columnName",
                        $this->groupingNames[DateGroupingFilterType::TYPE_MONTH],
                        $this->groupingNames[DateGroupingFilterType::TYPE_YEAR]
                    )
                );
                $this->addWhereClause($qb, DateGroupingFilterType::TYPE_MONTH);
                break;
            case DateGroupingFilterType::TYPE_QUARTER:
                $this->addFilter(DateGroupingFilterType::TYPE_QUARTER, $qb);
                $this->addFilter(DateGroupingFilterType::TYPE_YEAR, $qb);
                $qb->addSelect(
                    sprintf(
                        "CONCAT(%s, '-', %s) as $columnName",
                        $this->groupingNames[DateGroupingFilterType::TYPE_QUARTER],
                        $this->groupingNames[DateGroupingFilterType::TYPE_YEAR]
                    )
                );
                $this->addWhereClause($qb, DateGroupingFilterType::TYPE_QUARTER);
                break;
            default:
                $this->addFilter(DateGroupingFilterType::TYPE_YEAR, $qb);
                $qb->addSelect(
                    sprintf(
                        "%s as $columnName",
                        $this->groupingNames[DateGroupingFilterType::TYPE_YEAR]
                    )
                );
                $this->addWhereClause($qb, DateGroupingFilterType::TYPE_YEAR);
        }

        return true;
    }

    /**
     * @param $groupBy
     * @param QueryBuilder $qb
     */
    protected function addFilter($groupBy, QueryBuilder $qb)
    {
        $dataName = $this->generateDataName($this->get(FilterUtility::DATA_NAME_KEY) . self::COLUMN_NAME_SUFFIX);
        $columnName = $this->getOr('column_name', $dataName) . $groupBy;
        $this->groupingNames[$groupBy] = sprintf('%s(%s)', $groupBy, $this->get(FilterUtility::DATA_NAME_KEY));
        $select = sprintf('%s as %s', $this->groupingNames[$groupBy], $columnName);
        $qb->addSelect($select)->addGroupBy($columnName);
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    protected function parseData($data)
    {
        if (is_array($data) && !$data['value']) {
            $data['value'] = DateGroupingFilterType::TYPE_DAY;
        }

        return $data;
    }

    /**
     * @param QueryBuilder $qb
     * @param $filterType
     */
    protected function addWhereClause(QueryBuilder $qb, $filterType)
    {
        $whereClauseParameters = $qb->getParameters();
        $extraWhereClauses = $qb->getDQLPart('where') ?
            $this->getExtraWhereClauses($qb->getDQLPart('where')->getParts()) :
            '';

        $firstSubQueryBuilder = $this->generateSubQuery(
            $filterType,
            $this->config['first_calendar_table_for_grouping'],
            $this->config['calendar_column_for_grouping'],
            $this->config['first_joined_table'],
            $this->config['joined_column'],
            $extraWhereClauses,
            $whereClauseParameters
        );

        $secondSubQueryBuilder = $this->generateSubQuery(
            $filterType,
            $this->config['second_calendar_table_for_grouping'],
            $this->config['calendar_column_for_grouping'],
            $this->config['second_joined_table'],
            $this->config['joined_column'],
            $extraWhereClauses,
            $whereClauseParameters
        );

        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->notIn(
                    sprintf(
                        '%s(%s.%s)',
                        $filterType,
                        $this->config['calendar_table'],
                        $this->config['calendar_column']
                    ),
                    $firstSubQueryBuilder->getDQL()
                ),
                $qb->expr()->andX(
                    $qb->expr()->in(
                        sprintf(
                            '%s(%s.%s)',
                            $filterType,
                            $this->config['calendar_table'],
                            $this->config['calendar_column']
                        ),
                        $secondSubQueryBuilder->getDQL()
                    ),
                    $qb->expr()->isNotNull($this->config['not_nullable_field'])
                )
            )
        );
    }

    /**
     * @param $filterType
     * @param $calendarTableForGrouping
     * @param $calendarColumnForGrouping
     * @param $joinedTable
     * @param $joinedColumn
     * @param $extraWhereClauses
     * @param $extraWhereParameters
     * @return QueryBuilder
     */
    private function generateSubQuery(
        $filterType,
        $calendarTableForGrouping,
        $calendarColumnForGrouping,
        $joinedTable,
        $joinedColumn,
        $extraWhereClauses,
        $extraWhereParameters
    ) {
        $subQueryBuilder = $this->entityManager->createQueryBuilder();
        $extraWhereClauses = str_replace(
            $this->config['data_name'],
            sprintf('%s.%s', $calendarTableForGrouping, $calendarColumnForGrouping),
            $extraWhereClauses
        );

        return $subQueryBuilder
            ->select(
                sprintf(
                    '%s(%s.%s)',
                    $filterType,
                    $calendarTableForGrouping,
                    $calendarColumnForGrouping
                )
            )
            ->from($this->config['calendar_entity'], $calendarTableForGrouping)
            ->innerJoin($this->config['target_entity'], $joinedTable)
            ->where(
                sprintf(
                    '(CAST(%s.%s as %s) = CAST(%s.%s as %s) %s)',
                    $calendarTableForGrouping,
                    $calendarColumnForGrouping,
                    $this->config['target_column'],
                    $joinedTable,
                    $joinedColumn,
                    $this->config['target_column'],
                    $extraWhereClauses
                )
            )
            ->setParameters($extraWhereParameters)
            ->groupBy(sprintf('%s.%s', $calendarTableForGrouping, $calendarColumnForGrouping));
    }

    /**
     * Resolve options from configuration or constants.
     */
    private function resolveConfiguration()
    {
        $this->config['data_name'] = $this->get(FilterUtility::DATA_NAME_KEY);
        $this->config['calendar_table'] = $this->getOr('calendar_table', self::CALENDAR_TABLE);
        $this->config['calendar_column'] = $this->getOr('calendar_column', self::CALENDAR_COLUMN);
        $this->config['first_calendar_table_for_grouping'] = $this
            ->getOr('first_calendar_table_for_grouping', self::FIRST_CALENDAR_TABLE_FOR_GROUPING);
        $this->config['second_calendar_table_for_grouping'] = $this
            ->getOr('second_calendar_table_for_grouping', self::SECOND_CALENDAR_TABLE_FOR_GROUPING);
        $this->config['calendar_column_for_grouping'] = $this
            ->getOr('calendar_column_for_grouping', self::CALENDAR_COLUMN_FOR_GROUPING);
        $this->config['first_joined_table'] = $this->getOr('first_joined_table', self::FIRST_JOINED_TABLE);
        $this->config['second_joined_table'] = $this->getOr('second_joined_table', self::SECOND_JOINED_TABLE);
        $this->config['joined_column'] = $this->get('joined_column');
        $this->config['target_column'] = $this->getOr('target_column', self::TARGET_COLUMN);
        $this->config['not_nullable_field'] = $this->get('not_nullable_field');
        $this->config['calendar_entity'] = $this->get('calendar_entity');
        $this->config['target_entity'] = $this->get('target_entity');
    }

    /**
     * @param string $dataName
     * @return mixed
     */
    private function generateDataName($dataName)
    {
        return str_replace('.', '', $dataName);
    }

    /**
     * @param $whereClauseParts
     * @return string
     */
    public function getExtraWhereClauses($whereClauseParts)
    {
        $extraWhereClauses = '';
        foreach ($whereClauseParts as $whereClausePart) {
            if (strpos($whereClausePart, $this->config['data_name']) !== false) {
                $extraWhereClauses = sprintf('%s AND ( %s )', $extraWhereClauses, $whereClausePart);
            }
        }

        return $extraWhereClauses;
    }
}
