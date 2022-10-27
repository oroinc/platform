<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Filter;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ActivityListBundle\Filter\ActivityListFilterHelper;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\FilterBundle\Filter\FilterBagInterface;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Symfony\Component\Form\Form;

class ActivityListFilterHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testAddFiltersToQuery()
    {
        $filterBag = $this->createMock(FilterBagInterface::class);
        $routingHelper = $this->createMock(EntityRoutingHelper::class);
        $chainProvider = $this->createMock(ActivityListChainProvider::class);

        $dateTimeFilter = $this->createMock(FilterInterface::class);
        $choiceFilter = $this->createMock(FilterInterface::class);
        $filterBag->expects($this->exactly(2))
            ->method('getFilter')
            ->willReturnMap([
                ['datetime', $dateTimeFilter],
                ['choice', $choiceFilter]
            ]);

        $filterData = [
            'dateRange' => [
                'value' => 'dateRangeFilter'
            ],
            'activityType' => [
                'value' => ['Acme\TestBundle\Entity\TestEntity']
            ]
        ];

        $filter = new ActivityListFilterHelper($filterBag, $routingHelper, $chainProvider);

        $qb = $this->createMock(QueryBuilder::class);

        $dateTimeFilter->expects($this->once())
            ->method('init')
            ->with('updatedAt', ['data_name' => 'activity.updatedAt']);

        $dateTimeForm = $this->createMock(Form::class);
        $dateTimeFilter->expects($this->once())
            ->method('getForm')
            ->willReturn($dateTimeForm);
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
            ->willReturn('Acme\TestBundle\Entity\TestEntity');
        $chainProvider->expects($this->once())
            ->method('getSupportedActivities')
            ->willReturn(['Acme\TestBundle\Entity\TestEntity']);

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

        $choiceForm = $this->createMock(Form::class);
        $choiceFilter->expects($this->once())
            ->method('getForm')
            ->willReturn($choiceForm);
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
