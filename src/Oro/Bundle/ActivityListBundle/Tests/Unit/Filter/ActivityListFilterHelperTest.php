<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Filter;

use Oro\Bundle\ActivityListBundle\Filter\ActivityListFilterHelper;

class ActivityListFilterHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testAddFiltersToQuery()
    {
        $dateTimeFilter = $this->getMockBuilder('Oro\Bundle\FilterBundle\Filter\DateTimeRangeFilter')
            ->disableOriginalConstructor()->getMock();

        $choiceFilter = $this->getMockBuilder('Oro\Bundle\FilterBundle\Filter\ChoiceFilter')
            ->disableOriginalConstructor()->getMock();

        $routingHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper')
            ->disableOriginalConstructor()->getMock();

        $chainProvider = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider')
            ->disableOriginalConstructor()->getMock();

        $filterData = [
            'dateRange' => [
                'value' => 'dateRangeFilter'
            ],
            'activityType' => [
                'value' => ['Acme\TestBundle\Entity\TestEntity']
            ]
        ];

        $filter = new ActivityListFilterHelper($dateTimeFilter, $choiceFilter, $routingHelper, $chainProvider);

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()->getMock();

        $dateTimeFilter->expects($this->once())
            ->method('init')
            ->with('updatedAt', ['data_name' => 'activity.updatedAt']);

        $dateTimeForm = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()->getMock();

        $dateTimeFilter->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($dateTimeForm));

        $dateTimeForm->expects($this->once())
            ->method('isSubmitted')->willReturn(false);

        $dateTimeForm->expects($this->once())
            ->method('submit')
            ->with(['value' => 'dateRangeFilter']);

        $dateTimeFilter->expects($this->once())
            ->method('apply');

        $routingHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with('Acme\TestBundle\Entity\TestEntity')
            ->will($this->returnValue('Acme\TestBundle\Entity\TestEntity'));
        $chainProvider->expects($this->once())
            ->method('getSupportedActivities')
            ->will($this->returnValue(['Acme\TestBundle\Entity\TestEntity']));

        $choiceFilter->expects($this->once())
            ->method('init')
            ->with(
                'relatedActivityClass',
                [
                    'data_name' => 'activity.relatedActivityClass',
                    'options'   => [
                        'field_options' => [
                            'multiple' => true,
                            'choices'  => ['Acme\TestBundle\Entity\TestEntity']
                        ]
                    ]
                ]
            );

        $choiceForm = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()->getMock();

        $choiceFilter->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($choiceForm));

        $choiceForm->expects($this->once())
            ->method('isSubmitted')->willReturn(false);

        $choiceForm->expects($this->once())
            ->method('submit')
            ->with(['value' => ['Acme\TestBundle\Entity\TestEntity']]);

        $choiceFilter->expects($this->once())
            ->method('apply');

        $filter->addFiltersToQuery($qb, $filterData);
    }
}
