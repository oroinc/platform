<?php

namespace Oro\Bundle\ActivityListBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\ChoiceFilter;
use Oro\Bundle\FilterBundle\Filter\DateTimeRangeFilter;

class ActivityListFilterHelper
{
    /** @var  DateTimeRangeFilter */
    protected $dateTimeRangeFilter;

    /** @var ChoiceFilter */
    protected $choiceFilter;

    /** @var EntityRoutingHelper */
    protected $routingHelper;

    /** @var ActivityListChainProvider */
    protected $chainProvider;

    /**
     * @param DateTimeRangeFilter       $dateTimeRangeFilter
     * @param ChoiceFilter              $choiceFilter
     * @param EntityRoutingHelper       $routingHelper
     * @param ActivityListChainProvider $chainProvider
     */
    public function __construct(
        DateTimeRangeFilter $dateTimeRangeFilter,
        ChoiceFilter $choiceFilter,
        EntityRoutingHelper $routingHelper,
        ActivityListChainProvider $chainProvider
    ) {
        $this->dateTimeRangeFilter = $dateTimeRangeFilter;
        $this->choiceFilter        = $choiceFilter;
        $this->routingHelper       = $routingHelper;
        $this->chainProvider       = $chainProvider;
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
            $this->dateTimeRangeFilter->init(
                $rangeField,
                ['data_name' => sprintf('%s.%s', $activityListAlias, $rangeField)]
            );
            $datetimeForm = $this->dateTimeRangeFilter->getForm();
            if (!$datetimeForm->isSubmitted()) {
                $datetimeForm->submit($filterData['dateRange']);
            }
            $this->dateTimeRangeFilter->apply($dataSourceAdapter, $datetimeForm->getData());
        }
        if (isset($filterData['activityType'])) {
            $routingHelper = $this->routingHelper;

            $filterData['activityType']['value'] = array_map(
                function ($activityClass) use ($routingHelper) {
                    return $routingHelper->resolveEntityClass($activityClass);
                },
                $filterData['activityType']['value']
            );

            $this->choiceFilter->init(
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
            $typeForm = $this->choiceFilter->getForm();
            if (!$typeForm->isSubmitted()) {
                $typeForm->submit($filterData['activityType']);
            }
            $this->choiceFilter->apply($dataSourceAdapter, $typeForm->getData());
        }
    }
}
