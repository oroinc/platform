<?php

namespace Oro\Bundle\ActivityListBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterBagInterface;

/**
 * Provides a set of utility methods to filter data by an activity list.
 */
class ActivityListFilterHelper
{
    /** @var FilterBagInterface */
    private $filterBag;

    /** @var EntityRoutingHelper */
    private $routingHelper;

    /** @var ActivityListChainProvider */
    private $chainProvider;

    public function __construct(
        FilterBagInterface $filterBag,
        EntityRoutingHelper $routingHelper,
        ActivityListChainProvider $chainProvider
    ) {
        $this->filterBag = $filterBag;
        $this->routingHelper = $routingHelper;
        $this->chainProvider = $chainProvider;
    }

    /**
     * @param QueryBuilder $qb
     * @param array        $filterData
     * @param string       $rangeField
     * @param string       $activityListAlias
     */
    public function addFiltersToQuery(
        QueryBuilder $qb,
        $filterData,
        $rangeField = 'updatedAt',
        $activityListAlias = 'activity'
    ) {
        $dataSourceAdapter = new OrmFilterDatasourceAdapter($qb);
        if (isset($filterData['dateRange'])) {
            $dateTimeRangeFilter = $this->filterBag->getFilter('datetime');
            $dateTimeRangeFilter->init(
                $rangeField,
                ['data_name' => sprintf('%s.%s', $activityListAlias, $rangeField)]
            );
            $datetimeForm = $dateTimeRangeFilter->getForm();
            if (!$datetimeForm->isSubmitted()) {
                $datetimeForm->submit($filterData['dateRange']);
            }
            $dateTimeRangeFilter->apply($dataSourceAdapter, $datetimeForm->getData());
        }
        if (isset($filterData['activityType'])) {
            $routingHelper = $this->routingHelper;

            $filterData['activityType']['value'] = array_map(
                function ($activityClass) use ($routingHelper) {
                    return $routingHelper->resolveEntityClass($activityClass);
                },
                $filterData['activityType']['value']
            );

            $choiceFilter = $this->filterBag->getFilter('choice');
            $choiceFilter->init(
                'relatedActivityClass',
                [
                    'data_name' => sprintf('%s.relatedActivityClass', $activityListAlias),
                    'options'   => [
                        'field_options' => [
                            'multiple' => true,
                            'choices' => $this->chainProvider->getSupportedActivities()
                        ]
                    ]
                ]
            );
            $typeForm = $choiceFilter->getForm();
            if (!$typeForm->isSubmitted()) {
                $typeForm->submit($filterData['activityType']);
            }
            $choiceFilter->apply($dataSourceAdapter, $typeForm->getData());
        }
    }
}
