<?php

namespace Oro\Bundle\DashboardBundle\Filter;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Utils\DateFilterModifier;

class DateFilterProcessor
{

    /** @var DateRangeFilter */
    protected $dateFilter;

    /** @var DateFilterModifier */
    protected $modifier;

    /**
     * @param DateRangeFilter    $filter
     * @param DateFilterModifier $modifier
     */
    public function __construct(DateRangeFilter $filter, DateFilterModifier $modifier)
    {
        $this->dateFilter = $filter;
        $this->modifier   = $modifier;
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
}
