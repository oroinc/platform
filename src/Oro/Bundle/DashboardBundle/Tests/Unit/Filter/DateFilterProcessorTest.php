<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Filter;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DashboardBundle\Filter\DateFilterProcessor;
use Oro\Bundle\DashboardBundle\Filter\DateRangeFilter;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\FilterBundle\Utils\DateFilterModifier;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class DateFilterProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var DateRangeFilter|\PHPUnit\Framework\MockObject\MockObject */
    private $dateFilter;

    /** @var DateFilterModifier|\PHPUnit\Framework\MockObject\MockObject */
    private $modifier;

    /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
    private $localeSettings;

    /** @var DateFilterProcessor */
    private $processor;

    protected function setUp()
    {
        $this->dateFilter = $this->createMock(DateRangeFilter::class);
        $this->modifier = $this->createMock(DateFilterModifier::class);
        $this->localeSettings = $this->createMock(LocaleSettings::class);

        $this->processor = new DateFilterProcessor($this->dateFilter, $this->modifier, $this->localeSettings);
    }

    public function testProcess()
    {
        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $qb */
        $qb = $this->createMock(QueryBuilder::class);

        $this->dateFilter->expects($this->once())
            ->method('init')
            ->with('datetime', [FilterUtility::DATA_NAME_KEY => 'test_field']);

        $this->modifier->expects($this->once())
            ->method('modify')
            ->with(
                [
                    'a' => 'b',
                    'value' => [
                        'start' => 'start_value',
                        'end' => 'end_value'
                    ]
                ],
                ['start', 'end'],
                false
            )
            ->willReturn(['modifiedData']);

        $this->dateFilter->expects($this->once())
            ->method('apply')
            ->with(new OrmFilterDatasourceAdapter($qb), ['modifiedData']);

        $this->processor->process($qb, ['start' => 'start_value', 'end' => 'end_value', 'a' => 'b'], 'test_field');
    }

    public function testGetModifiedDateData()
    {
        $this->modifier->expects($this->once())
            ->method('modify')
            ->with(
                [
                    'a' => 'b',
                    'value' => [
                        'start' => 'start_value',
                        'end' => 'end_value'
                    ]
                ],
                ['start', 'end'],
                false
            )
            ->willReturn(['modifiedData']);

        $this->assertEquals(
            ['modifiedData'],
            $this->processor->getModifiedDateData(['start' => 'start_value', 'end' => 'end_value', 'a' => 'b'])
        );
    }

    public function testPrepareDateFromString()
    {
        $this->localeSettings->expects($this->once())
            ->method('getTimeZone')
            ->willReturn('UTC');

        $this->assertEquals(
            new \DateTime('2016-01-01 13:00:00', new \DateTimeZone('UTC')),
            $this->processor->prepareDate('2016-01-01 13:00:00')
        );
    }

    public function testPrepareDateFromDateTime()
    {
        $date = new \DateTime('now');

        $this->localeSettings->expects($this->never())
            ->method('getTimeZone');

        $this->assertSame($date, $this->processor->prepareDate($date));
    }

    public function testApplyDateRangeFilterToQueryMoreThan()
    {
        $this->localeSettings->expects($this->once())
            ->method('getTimeZone')
            ->willReturn('UTC');

        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $qb */
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('alias >= :start')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('start', new \DateTime('2016-01-01 13:00:00', new \DateTimeZone('UTC')))
            ->willReturnSelf();

        $this->modifier->expects($this->once())
            ->method('modify')
            ->with(
                [
                    'value' => [
                        'start' => '2016-01-01 13:00:00',
                        'end' => 'end_value'
                    ]
                ],
                ['start', 'end'],
                false
            )
            ->willReturn(
                [
                    'value' => [
                        'start' => '2016-01-01 13:00:00',
                        'end' => 'end_value'
                    ],
                    'type' => AbstractDateFilterType::TYPE_MORE_THAN
                ]
            );

        $this->processor->applyDateRangeFilterToQuery(
            $qb,
            ['start' => '2016-01-01 13:00:00', 'end' => 'end_value'],
            'alias'
        );
    }

    public function testApplyDateRangeFilterToQueryLessThan()
    {
        $this->localeSettings->expects($this->once())
            ->method('getTimeZone')
            ->willReturn('UTC');

        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $qb */
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('alias <= :end')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('end', new \DateTime('2016-01-01 13:00:00', new \DateTimeZone('UTC')))
            ->willReturnSelf();

        $this->modifier->expects($this->once())
            ->method('modify')
            ->with(
                [
                    'value' => [
                        'start' => 'start_value',
                        'end' => '2016-01-01 13:00:00'
                    ]
                ],
                ['start', 'end'],
                false
            )
            ->willReturn(
                [
                    'value' => [
                        'start' => 'start_value',
                        'end' => '2016-01-01 13:00:00'
                    ],
                    'type' => AbstractDateFilterType::TYPE_LESS_THAN
                ]
            );

        $this->processor->applyDateRangeFilterToQuery(
            $qb,
            ['start' => 'start_value', 'end' => '2016-01-01 13:00:00'],
            'alias'
        );
    }

    public function testApplyDateRangeFilterToQueryAllTime()
    {
        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $qb */
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->never())
            ->method($this->anything());

        $this->modifier->expects($this->once())
            ->method('modify')
            ->with(
                [
                    'value' => [
                        'start' => 'start_value',
                        'end' => 'end_value'
                    ]
                ],
                ['start', 'end'],
                false
            )
            ->willReturn(
                [
                    'value' => [
                        'start' => 'start_value',
                        'end' => 'end_value'
                    ],
                    'type' => AbstractDateFilterType::TYPE_ALL_TIME
                ]
            );

        $this->processor->applyDateRangeFilterToQuery($qb, ['start' => 'start_value', 'end' => 'end_value'], 'alias');
    }

    public function testApplyDateRangeFilterToQuery()
    {
        $this->localeSettings->expects($this->atLeastOnce())
            ->method('getTimeZone')
            ->willReturn('UTC');

        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $qb */
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->at(0))
            ->method('andWhere')
            ->with('alias >= :start')
            ->willReturnSelf();
        $qb->expects($this->at(1))
            ->method('setParameter')
            ->with('start', new \DateTime('2016-01-01 13:00:00', new \DateTimeZone('UTC')))
            ->willReturnSelf();
        $qb->expects($this->at(2))
            ->method('andWhere')
            ->with('alias <= :end')
            ->willReturnSelf();
        $qb->expects($this->at(3))
            ->method('setParameter')
            ->with('end', new \DateTime('2017-01-01 13:00:00', new \DateTimeZone('UTC')))
            ->willReturnSelf();

        $this->modifier->expects($this->once())
            ->method('modify')
            ->with(
                [
                    'value' => [
                        'start' => '2016-01-01 13:00:00',
                        'end' => '2017-01-01 13:00:00'
                    ]
                ],
                ['start', 'end'],
                false
            )
            ->willReturn(
                [
                    'value' => [
                        'start' => '2016-01-01 13:00:00',
                        'end' => '2017-01-01 13:00:00'
                    ],
                    'type' => 'unknown'
                ]
            );

        $this->processor->applyDateRangeFilterToQuery(
            $qb,
            ['start' => '2016-01-01 13:00:00', 'end' => '2017-01-01 13:00:00'],
            'alias'
        );
    }
}
