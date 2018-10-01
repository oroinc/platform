<?php

namespace Oro\Bundle\DashboardBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\FilterBundle\Utils\DateFilterModifier;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Modifies datagrid`s query builder according to the values of date range filter.
 */
class DateFilterProcessor
{
    /** @var DateRangeFilter */
    protected $dateFilter;

    /** @var DateFilterModifier */
    protected $modifier;

    /** @var LocaleSettings */
    private $localeSettings;

    /**
     * @param DateRangeFilter $filter
     * @param DateFilterModifier $modifier
     * @param LocaleSettings $localeSettings
     */
    public function __construct(DateRangeFilter $filter, DateFilterModifier $modifier, LocaleSettings $localeSettings)
    {
        $this->dateFilter = $filter;
        $this->modifier   = $modifier;
        $this->localeSettings = $localeSettings;
    }

    /**
     * @param QueryBuilder $qb
     * @param array        $dateData
     * @param string       $field
     */
    public function process(QueryBuilder $qb, array $dateData, $field)
    {
        $adapter = new OrmFilterDatasourceAdapter($qb);

        $this->dateFilter->init('datetime', [FilterUtility::DATA_NAME_KEY => $field]);
        $this->dateFilter->apply($adapter, $this->getModifiedDateData($dateData));
    }

    /**
     * @param array $dateData
     *
     * @return array
     */
    public function getModifiedDateData(array $dateData)
    {
        $dateData['value'] = [
            'start' => $dateData['start'],
            'end'   => $dateData['end']
        ];
        unset($dateData['start'], $dateData['end']);

        return $this->modifier->modify($dateData, ['start', 'end'], false);
    }

    /**
     * @param mixed $date
     *
     * @return \DateTime
     */
    public function prepareDate($date)
    {
        return $date instanceof \DateTime
            ? $date
            : new \DateTime($date, new \DateTimeZone($this->localeSettings->getTimeZone()));
    }

    /**
     * @param QueryBuilder $qb
     * @param $dateRange
     * @param $fieldAlias
     */
    public function applyDateRangeFilterToQuery(QueryBuilder $qb, $dateRange, $fieldAlias)
    {
        $dateRange = $this->getModifiedDateData($dateRange);
        switch ($dateRange['type']) {
            case AbstractDateFilterType::TYPE_MORE_THAN:
                $start = $this->prepareDate($dateRange['value']['start']);
                $qb->andWhere(QueryBuilderUtil::sprintf('%s >= :start', $fieldAlias))->setParameter('start', $start);
                break;
            case AbstractDateFilterType::TYPE_LESS_THAN:
                $end = $this->prepareDate($dateRange['value']['end']);
                $qb->andWhere(QueryBuilderUtil::sprintf('%s <= :end', $fieldAlias))->setParameter('end', $end);
                break;
            case AbstractDateFilterType::TYPE_ALL_TIME:
                return;
            default:
                $start = $this->prepareDate($dateRange['value']['start']);
                $end = $this->prepareDate($dateRange['value']['end']);
                $qb->andWhere(QueryBuilderUtil::sprintf('%s >= :start', $fieldAlias))->setParameter('start', $start);
                $qb->andWhere(QueryBuilderUtil::sprintf('%s <= :end', $fieldAlias))->setParameter('end', $end);
        }
    }
}
