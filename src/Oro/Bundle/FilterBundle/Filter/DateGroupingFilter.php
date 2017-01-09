<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateGroupingFilterType;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\ReportBundle\Entity\CalendarDate;

class DateGroupingFilter extends ChoiceFilter
{
    const COLUMN_NAME_SUFFIX = 'DateGroupingFilter';
    const SUB_QUERY_1 = '%s(calendarDate.date) NOT IN (SELECT %s(cd1.date) FROM %s as cd1  INNER JOIN %s as oo1 WHERE (CAST(cd1.date AS date) = CAST(oo1.createdAt AS date)) GROUP BY cd1.date)';
    const SUB_QUERY_2 = '%s(calendarDate.date) IN (SELECT %s(cd2.date) FROM %s as cd2  INNER JOIN %s as oo2 WHERE (CAST(cd2.date AS date) = CAST(oo2.createdAt AS date)) GROUP BY cd2.date)';

    /** @var array */
    protected $groupingNames = [];

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
     * @param string $dataName
     * @return mixed
     */
    private function generateDataName($dataName)
    {
        return str_replace('.', '', $dataName);
    }

    /**
     * @param QueryBuilder $qb
     * @param $filterType
     */
    protected function addWhereClause(QueryBuilder $qb, $filterType)
    {
        $qb->andWhere(
            sprintf(
                '(' . self::SUB_QUERY_1 . ')',
                $filterType,
                $filterType,
                CalendarDate::class,
                Order::class
            )
        );

        $qb->orWhere(
            sprintf(
                '(' . self::SUB_QUERY_2 . ' AND product.id is not null)',
                $filterType,
                $filterType,
                CalendarDate::class,
                Order::class
            )
        );
    }
}
