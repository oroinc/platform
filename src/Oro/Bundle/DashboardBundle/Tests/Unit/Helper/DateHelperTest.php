<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Helper;

use DateTime;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DashboardBundle\Helper\DateHelper;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class DateHelperTest extends OrmTestCase
{
    /** @var DateHelper */
    protected $helper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $settings;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclHelper;

    public function setUp()
    {
        $this->settings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();
        $this->settings->expects($this->any())
            ->method('getTimeZone')
            ->willReturn('UTC');
        $this->doctrine  = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->helper    = new DateHelper($this->settings, $this->doctrine, $this->aclHelper);
    }

    /**
     * @dataProvider datePeriodProvider
     */
    public function testGetDatePeriod($start, $end, $expects)
    {
        $start = new \DateTime($start, new \DateTimeZone('UTC'));
        $end   = new \DateTime($end, new \DateTimeZone('UTC'));

        $this->assertEquals($expects, $this->helper->getDatePeriod($start, $end));
    }

    public function datePeriodProvider()
    {
        return [
            'year'  => [
                '2007-01-01',
                '2011-01-01',
                [
                    '2007' => ['date' => '2007'],
                    '2008' => ['date' => '2008'],
                    '2009' => ['date' => '2009'],
                    '2010' => ['date' => '2010'],
                    '2011' => ['date' => '2011']
                ]
            ],
            'month' => [
                '2000-01-01',
                '2000-05-01',
                [
                    '2000-01' => ['date' => '2000-01-01'],
                    '2000-02' => ['date' => '2000-02-01'],
                    '2000-03' => ['date' => '2000-03-01'],
                    '2000-04' => ['date' => '2000-04-01'],
                    '2000-05' => ['date' => '2000-05-01']
                ]
            ],
            'week'  => [
                '2000-03-01',
                '2000-05-01',
                [
                    '2000-09' => ['date' => '2000-03-01'],
                    '2000-10' => ['date' => '2000-03-06'],
                    '2000-11' => ['date' => '2000-03-13'],
                    '2000-12' => ['date' => '2000-03-20'],
                    '2000-13' => ['date' => '2000-03-27'],
                    '2000-14' => ['date' => '2000-04-03'],
                    '2000-15' => ['date' => '2000-04-10'],
                    '2000-16' => ['date' => '2000-04-17'],
                    '2000-17' => ['date' => '2000-04-24'],
                    '2000-18' => ['date' => '2000-05-01'],
                ]
            ],
            'day'   => [
                '2000-03-01',
                '2000-03-04',
                [
                    '2000-03-01' => ['date' => '2000-03-01'],
                    '2000-03-02' => ['date' => '2000-03-02'],
                    '2000-03-03' => ['date' => '2000-03-03'],
                    '2000-03-04' => ['date' => '2000-03-04'],
                ]
            ],
            'hour'  => [
                '2000-03-01',
                '2000-03-02',
                [
                    '2000-03-01-00' => ['date' => '2000-03-01T00:00:00+00:00'],
                    '2000-03-01-01' => ['date' => '2000-03-01T01:00:00+00:00'],
                    '2000-03-01-02' => ['date' => '2000-03-01T02:00:00+00:00'],
                    '2000-03-01-03' => ['date' => '2000-03-01T03:00:00+00:00'],
                    '2000-03-01-04' => ['date' => '2000-03-01T04:00:00+00:00'],
                    '2000-03-01-05' => ['date' => '2000-03-01T05:00:00+00:00'],
                    '2000-03-01-06' => ['date' => '2000-03-01T06:00:00+00:00'],
                    '2000-03-01-07' => ['date' => '2000-03-01T07:00:00+00:00'],
                    '2000-03-01-08' => ['date' => '2000-03-01T08:00:00+00:00'],
                    '2000-03-01-09' => ['date' => '2000-03-01T09:00:00+00:00'],
                    '2000-03-01-10' => ['date' => '2000-03-01T10:00:00+00:00'],
                    '2000-03-01-11' => ['date' => '2000-03-01T11:00:00+00:00'],
                    '2000-03-01-12' => ['date' => '2000-03-01T12:00:00+00:00'],
                    '2000-03-01-13' => ['date' => '2000-03-01T13:00:00+00:00'],
                    '2000-03-01-14' => ['date' => '2000-03-01T14:00:00+00:00'],
                    '2000-03-01-15' => ['date' => '2000-03-01T15:00:00+00:00'],
                    '2000-03-01-16' => ['date' => '2000-03-01T16:00:00+00:00'],
                    '2000-03-01-17' => ['date' => '2000-03-01T17:00:00+00:00'],
                    '2000-03-01-18' => ['date' => '2000-03-01T18:00:00+00:00'],
                    '2000-03-01-19' => ['date' => '2000-03-01T19:00:00+00:00'],
                    '2000-03-01-20' => ['date' => '2000-03-01T20:00:00+00:00'],
                    '2000-03-01-21' => ['date' => '2000-03-01T21:00:00+00:00'],
                    '2000-03-01-22' => ['date' => '2000-03-01T22:00:00+00:00'],
                    '2000-03-01-23' => ['date' => '2000-03-01T23:00:00+00:00'],
                    '2000-03-02-00' => ['date' => '2000-03-02T00:00:00+00:00']
                ]
            ]
        ];
    }

    /**
     * @dataProvider keyProvider
     */
    public function testGetKey($start, $end, $expects)
    {
        $start = new \DateTime($start, new \DateTimeZone('UTC'));
        $end   = new \DateTime($end, new \DateTimeZone('UTC'));

        $row = [
            'yearCreated'  => '2000',
            'monthCreated' => '05',
            'dayCreated'   => '30',
            'weekCreated'  => '50',
            'hourCreated'  => '12',
            'dateCreated'  => '2000-05-30'
        ];

        $this->assertEquals($expects, $this->helper->getKey($start, $end, $row));
    }

    public function keyProvider()
    {
        return [
            'year'  => [
                '2007-01-01',
                '2011-01-01',
                '2000'
            ],
            'month' => [
                '2000-01-01',
                '2000-05-01',
                '2000-05'
            ],
            'week'  => [
                '2000-03-01',
                '2000-05-01',
                '2000-50'
            ],
            'day'   => [
                '2000-03-01',
                '2000-03-04',
                '2000-05-30'
            ],
            'hour'  => [
                '2000-03-01',
                '2000-03-02',
                '2000-05-30-12'
            ]
        ];
    }

    /**
     * @dataProvider addDatePartsSelectProvider
     */
    public function testAddDatePartsSelect($start, $end, $expects)
    {
        $start = new \DateTime($start, new \DateTimeZone('UTC'));
        $end   = new \DateTime($end, new \DateTimeZone('UTC'));

        $queryBuilder = new QueryBuilder($this->getTestEntityManager());
        $queryBuilder->select('id')
            ->from('Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\FirstTestBundle\Entity\TestEntity', 't');

        $this->helper->addDatePartsSelect($start, $end, $queryBuilder, 't.createdAt');

        $this->assertEquals($expects, $queryBuilder->getDQL());
    }

    public function addDatePartsSelectProvider()
    {
        return [
            'year'  => [
                '2007-01-01',
                '2011-01-01',
                'SELECT id, YEAR(t.createdAt) as yearCreated '
                . 'FROM Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\FirstTestBundle\Entity\TestEntity t '
                . 'GROUP BY yearCreated'
            ],
            'month' => [
                '2000-01-01',
                '2000-05-01',
                'SELECT id, YEAR(t.createdAt) as yearCreated, MONTH(t.createdAt) as monthCreated '
                . 'FROM Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\FirstTestBundle\Entity\TestEntity t '
                . 'GROUP BY yearCreated, monthCreated'
            ],
            'week'  => [
                '2000-03-01',
                '2000-05-01',
                'SELECT id, YEAR(t.createdAt) as yearCreated, WEEK(t.createdAt) as weekCreated '
                . 'FROM Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\FirstTestBundle\Entity\TestEntity t '
                . 'GROUP BY yearCreated, weekCreated'
            ],
            'day'   => [
                '2000-03-01',
                '2000-03-04',
                'SELECT id, YEAR(t.createdAt) as yearCreated, MONTH(t.createdAt) as monthCreated, '
                . 'DAY(t.createdAt) as dayCreated '
                . 'FROM Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\FirstTestBundle\Entity\TestEntity t '
                . 'GROUP BY yearCreated, monthCreated, dayCreated'
            ],
            'hour'  => [
                '2000-03-01',
                '2000-03-02',
                'SELECT id, DATE(t.createdAt) as dateCreated, HOUR(t.createdAt) as hourCreated '
                . 'FROM Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\FirstTestBundle\Entity\TestEntity t '
                . 'GROUP BY dateCreated, hourCreated'
            ]
        ];
    }

    public function testConvertToCurrentPeriodShouldReturnEmptyArrayIfDataAreEmpty()
    {
        $result = $this->helper->convertToCurrentPeriod(
            new DateTime(),
            new DateTime(),
            [],
            'row',
            'data'
        );

        $this->assertSame([], $result);
    }

    public function testConvertToCurrentPeriod()
    {
        $from = new DateTime('2015-05-10');
        $to   = new DateTime('2015-05-15');

        $data         = [
            [
                'yearCreated'  => '2015',
                'monthCreated' => '05',
                'dayCreated'   => '12',
                'cnt'          => 3,
            ],
            [
                'yearCreated'  => '2015',
                'monthCreated' => '05',
                'dayCreated'   => '14',
                'cnt'          => 5,
            ],
        ];
        $expectedData = [
            ['date' => '2015-05-10'],
            ['date' => '2015-05-11'],
            ['date' => '2015-05-12', 'count' => 3],
            ['date' => '2015-05-13'],
            ['date' => '2015-05-14', 'count' => 5],
            ['date' => '2015-05-15'],
        ];

        $actualData = $this->helper->convertToCurrentPeriod($from, $to, $data, 'cnt', 'count');

        $this->assertEquals($expectedData, $actualData);
    }

    public function testCombinePreviousDataWithCurrentPeriodShouldReturnEmptyArrayIfDataAreEmpty()
    {
        $result = $this->helper->combinePreviousDataWithCurrentPeriod(
            new DateTime(),
            new DateTime(),
            [],
            'row',
            'data'
        );

        $this->assertSame([], $result);
    }

    public function combinePreviousDataWithCurrentPeriodDataProvider()
    {
        return [
            'general'                         => [
                new DateTime('2015-05-05'),
                new DateTime('2015-05-10'),
                [
                    [
                        'yearCreated'  => '2015',
                        'monthCreated' => '05',
                        'dayCreated'   => '07',
                        'cnt'          => 5,
                    ]
                ],
                [
                    ['date' => '2015-05-10'],
                    ['date' => '2015-05-11'],
                    ['date' => '2015-05-12', 'count' => 5],
                    ['date' => '2015-05-13'],
                    ['date' => '2015-05-14'],
                    ['date' => '2015-05-15'],
                ]
            ],
            'empty_data_returns_empty_array'  => [
                new DateTime(),
                new DateTime(),
                [],
                []
            ],
            'long_period_last_days_of_month'  => [
                new DateTime('2015-05-19 23:00:00'),
                new DateTime('2015-08-30 00:00:00'),
                [
                    [
                        'yearCreated'  => '2015',
                        'monthCreated' => '07',
                        'dayCreated'   => '12',
                        'cnt'          => 5,
                    ]
                ],
                [
                    ['date' => '2015-08-01'],
                    ['date' => '2015-09-01'],
                    ['date' => '2015-10-01', 'count' => 5],
                    ['date' => '2015-11-01'],
                    ['date' => '2015-12-01'],
                ]
            ],
            'long_period_first_days_of_month' => [
                new DateTime('2015-03-02 22:00:00'),
                new DateTime('2015-08-01 00:00:00'),
                [
                    [
                        'yearCreated'  => '2015',
                        'monthCreated' => '07',
                        'dayCreated'   => '12',
                        'cnt'          => 5,
                    ]
                ],
                [
                    ['date' => '2015-08-01'],
                    ['date' => '2015-09-01'],
                    ['date' => '2015-10-01'],
                    ['date' => '2015-11-01'],
                    ['date' => '2015-12-01', 'count' => 5],
                ]
            ]
        ];
    }

    /**
     * @dataProvider combinePreviousDataWithCurrentPeriodDataProvider
     */
    public function testCombinePreviousDataWithCurrentPeriod($previousFrom, $previousTo, $data, $expectedData)
    {
        $actualData = $this->helper->combinePreviousDataWithCurrentPeriod(
            $previousFrom,
            $previousTo,
            $data,
            'cnt',
            'count'
        );

        $this->assertEquals($expectedData, $actualData);
    }

    /**
     * @dataProvider getPreviousDateTimeIntervalDataProvider
     *
     * @param Datetime $from
     * @param Datetime $to
     * @param Datetime $expectedStart
     * @param Datetime $expectedEnd
     */
    public function testGetPreviousDateTimeInterval(
        \Datetime $from,
        \Datetime $to,
        \Datetime $expectedStart,
        \Datetime $expectedEnd
    ) {
        list($start, $end) = $this->helper->getPreviousDateTimeInterval($from, $to);

        $this->assertEquals($start, $expectedStart);
        $this->assertEquals($end, $expectedEnd);
    }

    public function getPreviousDateTimeIntervalDataProvider()
    {
        $timezone = new \DateTimeZone('UTC');

        return [
            [
                'from'          => new \DateTime('2014-01-01 00:00:00', $timezone),
                'to'            => new \DateTime('2014-01-02 23:59:59', $timezone),
                'expectedStart' => new \DateTime('2013-12-30 00:00:00', $timezone),
                'expectedEnd'   => new \DateTime('2013-12-31 23:59:59', $timezone),
            ]
        ];
    }
}
