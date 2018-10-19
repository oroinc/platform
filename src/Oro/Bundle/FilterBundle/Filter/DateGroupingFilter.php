<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Utils\ArrayTrait;
use Oro\Bundle\ReportBundle\Entity\CalendarDate;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\Form\FormFactoryInterface;

class DateGroupingFilter extends ChoiceFilter
{
    use ArrayTrait;

    const NAME = 'date_grouping';

    const COLUMN_NAME = 'column_name';

    const CALENDAR_ENTITY = 'calendar_entity';
    const TARGET_ENTITY = 'target_entity';
    const NOT_NULLABLE_FIELD = 'not_nullable_field';
    const JOINED_COLUMN = 'joined_column';
    const JOINED_TABLE = 'joined_table';

    const TYPE_DAY = 'day';
    const TYPE_MONTH = 'month';
    const TYPE_QUARTER = 'quarter';
    const TYPE_YEAR = 'year';

    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param FormFactoryInterface $factory
     * @param FilterUtility $util
     * @param ManagerRegistry $registry
     */
    public function __construct(FormFactoryInterface $factory, FilterUtility $util, ManagerRegistry $registry)
    {
        parent::__construct($factory, $util);

        $this->registry = $registry;
    }

    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FRONTEND_TYPE_KEY] = 'select';

        parent::init($name, $params);
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

        /** @var OrmFilterDatasourceAdapter $ds */
        $qb = $ds->getQueryBuilder();
        $columnName = $this->get(self::COLUMN_NAME);

        switch ($data['value']) {
            case self::TYPE_DAY:
                $dayGroupingName = $this->addFilter(self::TYPE_DAY, $qb);
                $monthGroupingName = $this->addFilter(self::TYPE_MONTH, $qb);
                $yearGroupingName = $this->addFilter(self::TYPE_YEAR, $qb);

                $this->addSelect(
                    $qb,
                    [
                        $dayGroupingName,
                        $monthGroupingName,
                        $yearGroupingName,
                    ],
                    $columnName
                );

                break;
            case self::TYPE_MONTH:
                $monthGroupingName = $this->addFilter(self::TYPE_MONTH, $qb);
                $yearGroupingName = $this->addFilter(self::TYPE_YEAR, $qb);

                $this->addSelect(
                    $qb,
                    [
                        $monthGroupingName,
                        $yearGroupingName,
                    ],
                    $columnName
                );

                $this->addWhereClause($qb, self::TYPE_MONTH);
                break;
            case self::TYPE_QUARTER:
                $quarterGroupingName = $this->addFilter(self::TYPE_QUARTER, $qb);
                $yearGroupingName = $this->addFilter(self::TYPE_YEAR, $qb);

                $this->addSelect(
                    $qb,
                    [
                        $quarterGroupingName,
                        $yearGroupingName,
                    ],
                    $columnName
                );

                $this->addWhereClause($qb, self::TYPE_QUARTER);
                break;
            default:
                $yearGroupingName = $this->addFilter(self::TYPE_YEAR, $qb);

                $qb->addSelect(sprintf('%s as %s', $yearGroupingName, $columnName));

                $this->addWhereClause($qb, self::TYPE_YEAR);
                break;
        }

        $qb->addGroupBy($columnName);

        return true;
    }

    /**
     * If grouping by Day or Month make sure Year order is in same direction and keep multisort.
     *
     * @param OrmDatasource $datasource
     * @param String $sortKey
     * @param String $direction
     */
    public function applyOrderBy(OrmDatasource $datasource, String $sortKey, String $direction)
    {
        QueryBuilderUtil::checkField($sortKey);
        $direction = QueryBuilderUtil::getSortOrder($direction);

        /* @var OrmDatasource $datasource */
        $qb = $datasource->getQueryBuilder();
        $added = false;

        foreach ([self::TYPE_YEAR, self::TYPE_QUARTER, self::TYPE_MONTH, self::TYPE_DAY] as $groupBy) {
            $columnName = $this->getSelectAlias($groupBy);
            $groupingName = $this->getSelectClause($groupBy);

            /** @var Select $select */
            foreach ($qb->getDQLPart('select') as $select) {
                foreach ($select->getParts() as $part) {
                    if ($groupingName === $part) {
                        $qb->addOrderBy($columnName, $direction);
                        $added = true;
                    }
                }
            }
        }

        if (!$added) {
            $qb->addOrderBy($sortKey, $direction);
        }
    }

    /**
     * @param string $groupBy
     * @param QueryBuilder $qb
     * @return string
     */
    protected function addFilter($groupBy, QueryBuilder $qb)
    {
        $columnName = $this->getSelectAlias($groupBy);
        $groupingName = $this->getSelectClause($groupBy);

        $qb->addSelect(sprintf('%s as %s', $groupingName, $columnName))
            ->addGroupBy($columnName);

        return $groupingName;
    }

    /**
     * @param string $postfix
     * @return string
     */
    private function getSelectAlias($postfix)
    {
        $selectAlias = $this->get(self::COLUMN_NAME) . ucfirst($postfix);
        QueryBuilderUtil::checkField($selectAlias);

        return $selectAlias;
    }

    /**
     * @param string $groupBy
     * @return string
     */
    private function getSelectClause($groupBy)
    {
        return sprintf('%s(%s)', $groupBy, $this->getDataFieldName());
    }

    /**
     * @param QueryBuilder $qb
     * @param array $parts
     * @param string $columnName
     */
    protected function addSelect(QueryBuilder $qb, array $parts, $columnName)
    {
        QueryBuilderUtil::checkIdentifier($columnName);
        $select = implode(', \'-\', ', $parts);

        $qb->addSelect(sprintf('CONCAT(%s) as %s', $select, $columnName));
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    protected function parseData($data)
    {
        if (is_array($data) && !$data['value']) {
            $data['value'] = self::TYPE_DAY;
        }

        return $data;
    }

    /**
     * @param QueryBuilder $qb
     * @param string $filterType
     */
    protected function addWhereClause(QueryBuilder $qb, $filterType)
    {
        $extraWhereClauses = !$qb->getDQLPart('where')
            ? null :
            $this->getExtraWhereClauses($qb->getDQLPart('where')->getParts());
        
        $whereClauseParameters = $this->getExtraWhereParameters($qb->getParameters(), $extraWhereClauses);
        
        $usedDates = $this->getUsedDates(
            $filterType,
            'calendarDateTableForGrouping',
            'date',
            $this->get(self::JOINED_TABLE),
            $this->get(self::JOINED_COLUMN),
            $extraWhereClauses,
            $whereClauseParameters
        );

        if (!$usedDates) {
            return;
        }

        $dataFieldName = $this->getDataFieldName();
        $notNullableField = $this->get(self::NOT_NULLABLE_FIELD);
        QueryBuilderUtil::checkField($notNullableField);

        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->andX(
                    $qb->expr()->notIn(
                        sprintf(
                            "CONCAT(%s(%s), '-', %s(%s))",
                            $filterType,
                            $dataFieldName,
                            self::TYPE_YEAR,
                            $dataFieldName
                        ),
                        $usedDates
                    )
                ),
                $qb->expr()->andX(
                    $qb->expr()->in(
                        sprintf(
                            "CONCAT(%s(%s), '-', %s(%s))",
                            $filterType,
                            $dataFieldName,
                            self::TYPE_YEAR,
                            $dataFieldName
                        ),
                        $usedDates
                    ),
                    $qb->expr()->isNotNull($notNullableField)
                )
            )
        );
    }

    /**
     * @param string $filterType
     * @param string $calendarTableForGrouping
     * @param string $calendarColumnForGrouping
     * @param string $joinedTable
     * @param string $joinedColumn
     * @param $extraWhereClauses
     * @param $extraWhereParameters
     * @return array|bool
     */
    private function getUsedDates(
        $filterType,
        $calendarTableForGrouping,
        $calendarColumnForGrouping,
        $joinedTable,
        $joinedColumn,
        $extraWhereClauses,
        $extraWhereParameters
    ) {
        QueryBuilderUtil::checkIdentifier($calendarTableForGrouping);
        QueryBuilderUtil::checkIdentifier($calendarColumnForGrouping);
        QueryBuilderUtil::checkIdentifier($joinedTable);
        QueryBuilderUtil::checkIdentifier($joinedColumn);

        /** @var EntityManager $manager */
        $manager = $this->registry->getManagerForClass(CalendarDate::class);

        $subQueryBuilder = $manager->createQueryBuilder();
        $extraWhereClauses = str_replace(
            $this->getDataFieldName(),
            sprintf('%s.%s', $calendarTableForGrouping, $calendarColumnForGrouping),
            $extraWhereClauses
        );

        $subQueryBuilder
            ->select(
                sprintf(
                    "DISTINCT CONCAT(%s(%s.%s), '-', %s(%s.%s))",
                    $filterType,
                    $calendarTableForGrouping,
                    $calendarColumnForGrouping,
                    self::TYPE_YEAR,
                    $calendarTableForGrouping,
                    $calendarColumnForGrouping
                )
            )
            ->from($this->getCalendarEntity(), $calendarTableForGrouping)
            ->innerJoin(
                $this->getTargetEntity(),
                $joinedTable,
                Join::WITH,
                sprintf(
                    '(CAST(%s.%s as %s) = CAST(%s.%s as %s) %s)',
                    $calendarTableForGrouping,
                    $calendarColumnForGrouping,
                    'date',
                    $joinedTable,
                    $joinedColumn,
                    'date',
                    $extraWhereClauses
                )
            );

        if ($extraWhereClauses != '') {
            $subQueryBuilder->setParameters($extraWhereParameters);
        }

        $datesArray = $subQueryBuilder->getQuery()->getArrayResult();

        return $this->arrayFlatten($datesArray);
    }

    /**
     * @param $whereClauseParts
     * @return string
     */
    protected function getExtraWhereClauses($whereClauseParts)
    {
        $extraWhereClauses = '';
        foreach ($whereClauseParts as $whereClausePart) {
            if (strpos($whereClausePart, $this->getDataFieldName()) !== false) {
                $extraWhereClauses = sprintf('%s AND ( %s )', $extraWhereClauses, $whereClausePart);
            }
        }

        return $extraWhereClauses;
    }

    /**
     * @param ArrayCollection $parameters
     * @param string $whereClauseParts
     * @return ArrayCollection
     */
    protected function getExtraWhereParameters(ArrayCollection $parameters, $whereClauseParts)
    {
        $extraParameters = new ArrayCollection();

        foreach ($parameters as $parameter) {
            /* @var $parameter Parameter */
            if (false !== strpos($whereClauseParts, sprintf(':%s', $parameter->getName()))) {
                $extraParameters->add($parameter);
            }
        }

        return $extraParameters;
    }

    /**
     * @return string
     */
    protected function getCalendarEntity()
    {
        return $this->get(self::CALENDAR_ENTITY);
    }

    /**
     * @return string
     */
    protected function getTargetEntity()
    {
        return $this->get(self::TARGET_ENTITY);
    }
}
