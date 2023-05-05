<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Helper;

use Carbon\Carbon;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DashboardBundle\Helper\DateHelper;
use Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\FirstTestBundle\Entity\TestEntity;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class DateHelperTest extends OrmTestCase
{
    private ManagerRegistry|MockObject $doctrine;

    private AclHelper|MockObject $aclHelper;

    private DateHelper $helper;

    protected function setUp(): void
    {
        $settings = $this->createMock(LocaleSettings::class);
        $settings->expects(self::any())
            ->method('getTimeZone')
            ->willReturn('UTC');
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->helper = new DateHelper($settings, $this->doctrine, $this->aclHelper);
    }

    /**
     * @dataProvider datePeriodProvider
     */
    public function testGetDatePeriod(string $start, string $end, ?string $scaleType, array $expects): void
    {
        $start = new \DateTime($start, new \DateTimeZone('UTC'));
        $end   = new \DateTime($end, new \DateTimeZone('UTC'));

        self::assertEquals($expects, $this->helper->getDatePeriod($start, $end, $scaleType));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function datePeriodProvider(): array
    {
        $expectedRangeWithScaleTime = [
            '2000-03-01-00' => ['date' => '2000-03-01T01:00:00+00:00'],
            '2000-03-01-01' => ['date' => '2000-03-01T02:00:00+00:00'],
            '2000-03-01-02' => ['date' => '2000-03-01T03:00:00+00:00'],
            '2000-03-01-03' => ['date' => '2000-03-01T04:00:00+00:00'],
            '2000-03-01-04' => ['date' => '2000-03-01T05:00:00+00:00'],
            '2000-03-01-05' => ['date' => '2000-03-01T06:00:00+00:00'],
            '2000-03-01-06' => ['date' => '2000-03-01T07:00:00+00:00'],
            '2000-03-01-07' => ['date' => '2000-03-01T08:00:00+00:00'],
            '2000-03-01-08' => ['date' => '2000-03-01T09:00:00+00:00'],
            '2000-03-01-09' => ['date' => '2000-03-01T10:00:00+00:00'],
            '2000-03-01-10' => ['date' => '2000-03-01T11:00:00+00:00'],
            '2000-03-01-11' => ['date' => '2000-03-01T12:00:00+00:00'],
            '2000-03-01-12' => ['date' => '2000-03-01T13:00:00+00:00'],
            '2000-03-01-13' => ['date' => '2000-03-01T14:00:00+00:00'],
            '2000-03-01-14' => ['date' => '2000-03-01T15:00:00+00:00'],
            '2000-03-01-15' => ['date' => '2000-03-01T16:00:00+00:00'],
            '2000-03-01-16' => ['date' => '2000-03-01T17:00:00+00:00'],
            '2000-03-01-17' => ['date' => '2000-03-01T18:00:00+00:00'],
            '2000-03-01-18' => ['date' => '2000-03-01T19:00:00+00:00'],
            '2000-03-01-19' => ['date' => '2000-03-01T20:00:00+00:00'],
            '2000-03-01-20' => ['date' => '2000-03-01T21:00:00+00:00'],
            '2000-03-01-21' => ['date' => '2000-03-01T22:00:00+00:00'],
            '2000-03-01-22' => ['date' => '2000-03-01T23:00:00+00:00'],
            '2000-03-01-23' => ['date' => '2000-03-02T00:00:00+00:00'],
            '2000-03-02-00' => ['date' => '2000-03-02T01:00:00+00:00'],
        ];
        return [
            'year'  => [
                '2007-01-01',
                '2011-01-01',
                null,
                [
                    '2007' => ['date' => '2007'],
                    '2008' => ['date' => '2008'],
                    '2009' => ['date' => '2009'],
                    '2010' => ['date' => '2010'],
                    '2011' => ['date' => '2011'],
                ],
            ],
            'month' => [
                '2000-01-01',
                '2000-05-01',
                null,
                [
                    '2000-01' => ['date' => '2000-01-01'],
                    '2000-02' => ['date' => '2000-02-01'],
                    '2000-03' => ['date' => '2000-03-01'],
                    '2000-04' => ['date' => '2000-04-01'],
                    '2000-05' => ['date' => '2000-05-01'],
                ],
            ],
            'week'  => [
                '2000-03-01',
                '2000-05-01',
                null,
                [
                    '2000-09' => [
                        'date' => '2000-03-01',
                        'dateStart' => '2000-03-01',
                        'dateEnd' => '2000-03-05',
                    ],
                    '2000-10' => [
                        'date' => '2000-03-06',
                        'dateStart' => '2000-03-06',
                        'dateEnd' => '2000-03-12',
                    ],
                    '2000-11' => [
                        'date' => '2000-03-13',
                        'dateStart' => '2000-03-13',
                        'dateEnd' => '2000-03-19',
                    ],
                    '2000-12' => [
                        'date' => '2000-03-20',
                        'dateStart' => '2000-03-20',
                        'dateEnd' => '2000-03-26',
                    ],
                    '2000-13' => [
                        'date' => '2000-03-27',
                        'dateStart' => '2000-03-27',
                        'dateEnd' => '2000-04-02',
                    ],
                    '2000-14' => [
                        'date' => '2000-04-03',
                        'dateStart' => '2000-04-03',
                        'dateEnd' => '2000-04-09',
                    ],
                    '2000-15' => [
                        'date' => '2000-04-10',
                        'dateStart' => '2000-04-10',
                        'dateEnd' => '2000-04-16',
                    ],
                    '2000-16' => [
                        'date' => '2000-04-17',
                        'dateStart' => '2000-04-17',
                        'dateEnd' => '2000-04-23',
                    ],
                    '2000-17' => [
                        'date' => '2000-04-24',
                        'dateStart' => '2000-04-24',
                        'dateEnd' => '2000-04-30',
                    ],
                    '2000-18' => [
                        'date' => '2000-05-01',
                        'dateStart' => '2000-05-01',
                        'dateEnd' => '2000-05-01',
                    ],
                ],
            ],
            'day'   => [
                '2000-03-01',
                '2000-03-04',
                null,
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
                null,
                $expectedRangeWithScaleTime,
            ],
            'scale year' => [
                '2007-01-01',
                '2007-01-02',
                'year',
                [
                    '2007' => ['date' => '2007'],
                ],
            ],
            'scale month' => [
                '2000-01-01',
                '2000-01-02',
                'month',
                [
                    '2000-01' => ['date' => '2000-01-01'],
                ],
            ],
            'scale week' => [
                '2000-03-01',
                '2000-03-02',
                'date',
                [
                    '2000-09' => [
                        'date' => '2000-03-01',
                        'dateStart' => '2000-03-01',
                        'dateEnd' => '2000-03-02',
                    ],
                ],
            ],
            'scale day' => [
                '2000-03-01',
                '2000-03-01',
                'day',
                [
                    '2000-03-01' => ['date' => '2000-03-01'],
                ],
            ],
            'scale time' => [
                '2000-03-01',
                '2000-03-02',
                'time',
                $expectedRangeWithScaleTime,
            ],
        ];
    }

    /**
     * @dataProvider keyProvider
     */
    public function testGetKey(string $start, string $end, ?string $scaleType, string $expects): void
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

        self::assertEquals($expects, $this->helper->getKey($start, $end, $row, $scaleType));
    }

    public function keyProvider(): array
    {
        return [
            'year' => [
                '2007-01-01',
                '2011-01-01',
                null,
                '2000'
            ],
            'month' => [
                '2000-01-01',
                '2000-05-01',
                null,
                '2000-05'
            ],
            'week' => [
                '2000-03-01',
                '2000-05-01',
                null,
                '2000-50'
            ],
            'day' => [
                '2000-03-01',
                '2000-03-04',
                null,
                '2000-05-30'
            ],
            'hour' => [
                '2000-03-01',
                '2000-03-02',
                null,
                '2000-05-30-12'
            ],
            'scale year' => [
                '2007-01-01',
                '2007-01-01',
                'year',
                '2000'
            ],
            'scale month' => [
                '2000-01-01',
                '2000-01-01',
                'month',
                '2000-05'
            ],
            'scale week' => [
                '2000-01-01',
                '2000-01-01',
                'date',
                '2000-50'
            ],
            'scale day' => [
                '2000-03-01',
                '2000-03-01',
                'day',
                '2000-05-30'
            ],
            'scale time' => [
                '2000-03-01',
                '2000-05-02',
                'time',
                '2000-05-30-12'
            ]
        ];
    }

    /**
     * @dataProvider addDatePartsSelectProvider
     */
    public function testAddDatePartsSelect(string $start, string $end, ?string $scaleType, string $expects): void
    {
        $start = new \DateTime($start, new \DateTimeZone('UTC'));
        $end   = new \DateTime($end, new \DateTimeZone('UTC'));

        $queryBuilder = new QueryBuilder($this->getTestEntityManager());
        $queryBuilder->select('id')
            ->from(TestEntity::class, 't');

        $this->helper->addDatePartsSelect($start, $end, $queryBuilder, 't.createdAt', $scaleType);

        self::assertEquals($expects, $queryBuilder->getDQL());
    }

    public function addDatePartsSelectProvider(): array
    {
        return [
            'year' => [
                '2007-01-01',
                '2011-01-01',
                null,
                'SELECT id, YEAR(t.createdAt) as yearCreated '
                . 'FROM Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\FirstTestBundle\Entity\TestEntity t '
                . 'GROUP BY yearCreated '
                . 'ORDER BY yearCreated ASC'
            ],
            'month' => [
                '2000-01-01',
                '2000-05-01',
                null,
                'SELECT id, YEAR(t.createdAt) as yearCreated, MONTH(t.createdAt) as monthCreated '
                . 'FROM Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\FirstTestBundle\Entity\TestEntity t '
                . 'GROUP BY yearCreated, monthCreated '
                . 'ORDER BY yearCreated ASC, monthCreated ASC'
            ],
            'week' => [
                '2000-03-01',
                '2000-05-01',
                null,
                'SELECT id, ISOYEAR(t.createdAt) as yearCreated, WEEK(t.createdAt) as weekCreated '
                . 'FROM Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\FirstTestBundle\Entity\TestEntity t '
                . 'GROUP BY yearCreated, weekCreated '
                . 'ORDER BY yearCreated ASC, weekCreated ASC'
            ],
            'day' => [
                '2000-03-01',
                '2000-03-04',
                null,
                'SELECT id, YEAR(t.createdAt) as yearCreated, MONTH(t.createdAt) as monthCreated, '
                . 'DAY(t.createdAt) as dayCreated '
                . 'FROM Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\FirstTestBundle\Entity\TestEntity t '
                . 'GROUP BY yearCreated, monthCreated, dayCreated '
                . 'ORDER BY yearCreated ASC, monthCreated ASC, dayCreated ASC'
            ],
            'hour' => [
                '2000-03-01',
                '2000-03-02',
                null,
                'SELECT id, DATE(t.createdAt) as dateCreated, HOUR(t.createdAt) as hourCreated '
                . 'FROM Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\FirstTestBundle\Entity\TestEntity t '
                . 'GROUP BY dateCreated, hourCreated '
                . 'ORDER BY dateCreated ASC, hourCreated ASC'
            ],
            'scale year' => [
                '2007-01-01',
                '2007-01-01',
                'year',
                'SELECT id, YEAR(t.createdAt) as yearCreated '
                . 'FROM Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\FirstTestBundle\Entity\TestEntity t '
                . 'GROUP BY yearCreated '
                . 'ORDER BY yearCreated ASC'
            ],
            'scale month' => [
                '2000-01-01',
                '2000-01-01',
                'month',
                'SELECT id, YEAR(t.createdAt) as yearCreated, MONTH(t.createdAt) as monthCreated '
                . 'FROM Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\FirstTestBundle\Entity\TestEntity t '
                . 'GROUP BY yearCreated, monthCreated '
                . 'ORDER BY yearCreated ASC, monthCreated ASC'
            ],
            'scale week' => [
                '2000-03-01',
                '2000-03-01',
                'date',
                'SELECT id, ISOYEAR(t.createdAt) as yearCreated, WEEK(t.createdAt) as weekCreated '
                . 'FROM Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\FirstTestBundle\Entity\TestEntity t '
                . 'GROUP BY yearCreated, weekCreated '
                . 'ORDER BY yearCreated ASC, weekCreated ASC'
            ],
            'scale day' => [
                '2000-03-01',
                '2000-05-04',
                'day',
                'SELECT id, YEAR(t.createdAt) as yearCreated, MONTH(t.createdAt) as monthCreated, '
                . 'DAY(t.createdAt) as dayCreated '
                . 'FROM Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\FirstTestBundle\Entity\TestEntity t '
                . 'GROUP BY yearCreated, monthCreated, dayCreated '
                . 'ORDER BY yearCreated ASC, monthCreated ASC, dayCreated ASC'
            ],
            'scale time' => [
                '2000-03-01',
                '2000-05-02',
                'time',
                'SELECT id, DATE(t.createdAt) as dateCreated, HOUR(t.createdAt) as hourCreated '
                . 'FROM Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\FirstTestBundle\Entity\TestEntity t '
                . 'GROUP BY dateCreated, hourCreated '
                . 'ORDER BY dateCreated ASC, hourCreated ASC'
            ]
        ];
    }

    public function testConvertToCurrentPeriodShouldReturnEmptyArrayIfDataAreEmptyByDefault(): void
    {
        $result = $this->helper->convertToCurrentPeriod(
            new \DateTime(),
            new \DateTime(),
            [],
            'row',
            'data'
        );

        self::assertSame([], $result);
    }

    public function testConvertToCurrentPeriodShouldReturnArrayWithEmptyValues(): void
    {
        $result = $this->helper->convertToCurrentPeriod(
            new \DateTime('2015-05-10', new \DateTimeZone('UTC')),
            new \DateTime('2015-05-13', new \DateTimeZone('UTC')),
            [],
            'row',
            'data',
            true
        );

        $expectedData = [
            ['date' => '2015-05-10'],
            ['date' => '2015-05-11'],
            ['date' => '2015-05-12'],
            ['date' => '2015-05-13'],
        ];

        self::assertSame($expectedData, $result);
    }

    /**
     * @dataProvider getConvertToCurrentPeriodDataProvider
     */
    public function testConvertToCurrentPeriod(?string $scaleType): void
    {
        $from = new \DateTime('2015-05-10');
        $to   = new \DateTime('2015-05-15');

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

        $actualData = $this->helper->convertToCurrentPeriod($from, $to, $data, 'cnt', 'count', false, $scaleType);

        self::assertEquals($expectedData, $actualData);
    }

    public function getConvertToCurrentPeriodDataProvider(): array
    {
        return [
            'without scale' => [
                'scaleType' => null,
            ],
            'with scale' => [
                'scaleType' => 'day',
            ],
        ];
    }

    public function testCombinePreviousDataWithCurrentPeriodShouldReturnEmptyArrayIfDataAreEmpty(): void
    {
        $result = $this->helper->combinePreviousDataWithCurrentPeriod(
            new \DateTime(),
            new \DateTime(),
            [],
            'row',
            'data'
        );

        self::assertSame([], $result);
    }

    public function combinePreviousDataWithCurrentPeriodDataProvider(): array
    {
        return [
            'general'                         => [
                new \DateTime('2015-05-05'),
                new \DateTime('2015-05-10'),
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
                new \DateTime(),
                new \DateTime(),
                [],
                []
            ],
            'long_period_last_days_of_month'  => [
                new \DateTime('2015-05-19 23:00:00'),
                new \DateTime('2015-08-30 00:00:00'),
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
                new \DateTime('2015-03-02 22:00:00'),
                new \DateTime('2015-08-01 00:00:00'),
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
    public function testCombinePreviousDataWithCurrentPeriod($previousFrom, $previousTo, $data, $expectedData): void
    {
        $actualData = $this->helper->combinePreviousDataWithCurrentPeriod(
            $previousFrom,
            $previousTo,
            $data,
            'cnt',
            'count'
        );

        self::assertEquals($expectedData, $actualData);
    }

    /**
     * @dataProvider getFormatStringsProvider
     */
    public function testGetFormatStrings(\DateTime $start, \DateTime $end, array $expectedValue): void
    {
        self::assertEquals($expectedValue, $this->helper->getFormatStrings($start, $end));
    }

    public function getFormatStringsProvider(): array
    {
        return [
            'year' => [
                new \DateTime('2010-01-01', new \DateTimeZone('UTC')),
                new \DateTime('2015-01-01', new \DateTimeZone('UTC')),
                [
                    'intervalString' => 'P1Y',
                    'valueStringFormat' => 'Y',
                    'viewType' => 'year',
                ]
            ],
            'month' => [
                new \DateTime('2010-01-01', new \DateTimeZone('UTC')),
                new \DateTime('2010-05-01', new \DateTimeZone('UTC')),
                [
                    'intervalString' => 'P1M',
                    'valueStringFormat' => 'Y-m',
                    'viewType' => 'month',
                ]
            ],
            'date' => [
                new \DateTime('2010-01-01', new \DateTimeZone('UTC')),
                new \DateTime('2010-03-15', new \DateTimeZone('UTC')),
                [
                    'intervalString' => 'P1W',
                    'valueStringFormat' => 'o-W',
                    'viewType' => 'date',
                ]
            ],
            'day' => [
                new \DateTime('2010-01-01', new \DateTimeZone('UTC')),
                new \DateTime('2010-01-15', new \DateTimeZone('UTC')),
                [
                    'intervalString' => 'P1D',
                    'valueStringFormat' => 'Y-m-d',
                    'viewType' => 'day',
                ]
            ],
            'time' => [
                new \DateTime('2010-01-01', new \DateTimeZone('UTC')),
                new \DateTime('2010-01-02', new \DateTimeZone('UTC')),
                [
                    'intervalString' => 'PT1H',
                    'valueStringFormat' => 'Y-m-d-H',
                    'viewType' => 'time',
                ]
            ],
        ];
    }

    /**
     * @dataProvider getKeyGeneratesKeysFromGetDatePeriod
     */
    public function testGetKeyGeneratesKeysFromGetDatePeriod(
        \DateTime $start,
        \DateTime $end,
        array $row,
        $expectedViewType
    ): void {
        $formatStrings = $this->helper->getFormatStrings($start, $end);
        self::assertEquals($expectedViewType, $formatStrings['viewType']);

        self::assertArrayHasKey(
            $this->helper->getKey($start, $end, $row),
            $this->helper->getDatePeriod($start, $end)
        );
    }

    public function getKeyGeneratesKeysFromGetDatePeriod(): array
    {
        return [
            'year' => [
                new \DateTime('2010-01-01', new \DateTimeZone('UTC')),
                new \DateTime('2015-01-01', new \DateTimeZone('UTC')),
                ['yearCreated' => '2010'],
                'year',
            ],
            'month' => [
                new \DateTime('2010-01-01', new \DateTimeZone('UTC')),
                new \DateTime('2010-05-01', new \DateTimeZone('UTC')),
                ['yearCreated' => '2010', 'monthCreated' => '4'],
                'month',
            ],
            'date' => [
                new \DateTime('2010-01-01', new \DateTimeZone('UTC')),
                new \DateTime('2010-03-15', new \DateTimeZone('UTC')),
                ['yearCreated' => '2010', 'weekCreated' => '1'],
                'date',
            ],
            'time with hour having 1 digit' => [
                new \DateTime('2010-01-01', new \DateTimeZone('UTC')),
                new \DateTime('2010-01-02', new \DateTimeZone('UTC')),
                ['dateCreated' => '2010-01-01', 'hourCreated' => '5'],
                'time',
            ],
            'time with hour having 2 digits' => [
                new \DateTime('2010-01-01', new \DateTimeZone('UTC')),
                new \DateTime('2010-01-02', new \DateTimeZone('UTC')),
                ['dateCreated' => '2010-01-01', 'hourCreated' => '11'],
                'time',
            ],
        ];
    }

    /**
     * @dataProvider getPeriodDataProvider
     */
    public function testGetPeriod(array $dateRange, bool $isFullStartDate, array $expectedResult): void
    {
        $entityClassName = \stdClass::class;
        $entityFieldName = 'bar';

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::any())
            ->method('getSingleScalarResult')
            ->willReturn(DateHelper::MIN_DATE . ' 10:00:00');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::any())
            ->method('select')
            ->with(sprintf('COALESCE(MIN(e.%s), :defaultMinDate) as val', $entityFieldName))
            ->willReturnSelf();
        $queryBuilder->expects(self::any())
            ->method('setParameter')
            ->with('defaultMinDate', DateHelper::MIN_DATE)
            ->willReturnSelf();

        $this->aclHelper->expects(self::any())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->expects(self::any())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);

        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with($entityClassName)
            ->willReturn($entityRepository);

        self::assertEquals(
            $expectedResult,
            $this->helper->getPeriod($dateRange, $entityClassName, $entityFieldName, $isFullStartDate)
        );
    }

    public function getPeriodDataProvider(): array
    {
        $currentDateTime = Carbon::now(new \DateTimeZone('UTC'));

        $defaultMinDate = new \DateTime(DateHelper::MIN_DATE, new \DateTimeZone('UTC'));
        $defaultMinDateTime = new \DateTime(DateHelper::MIN_DATE . ' 10:00:00', new \DateTimeZone('UTC'));
        $startOfYear = new \DateTime((clone $currentDateTime)->startOfYear(), new \DateTimeZone('UTC'));
        $endOfYear = new \DateTime((clone $currentDateTime)->endOfYear(), new \DateTimeZone('UTC'));

        return [
            'all time' => [
                'dateRange' => [
                    'start' => null,
                    'end' => null,
                    'type' => AbstractDateFilterType::TYPE_ALL_TIME,
                ],
                'isFullStartDate' => false,
                'expectedResult' => [
                    $defaultMinDateTime,
                    null,
                ],
            ],
            'earlier than' => [
                'dateRange' => [
                    'start' => null,
                    'end' => $endOfYear,
                    'type' => AbstractDateFilterType::TYPE_LESS_THAN,
                ],
                'isFullStartDate' => false,
                'expectedResult' => [
                    $defaultMinDateTime,
                    $endOfYear,
                ],
            ],
            'as is' => [
                'dateRange' => [
                    'start' => $startOfYear,
                    'end' => $endOfYear,
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
                ],
                'isFullStartDate' => false,
                'expectedResult' => [
                    $startOfYear,
                    $endOfYear,
                ],
            ],
            'all time full date' => [
                'dateRange' => [
                    'start' => null,
                    'end' => null,
                    'type' => AbstractDateFilterType::TYPE_ALL_TIME,
                ],
                'isFullStartDate' => true,
                'expectedResult' => [
                    $defaultMinDate,
                    null,
                ],
            ],
            'earlier than full date' => [
                'dateRange' => [
                    'start' => null,
                    'end' => $endOfYear,
                    'type' => AbstractDateFilterType::TYPE_LESS_THAN,
                ],
                'isFullStartDate' => true,
                'expectedResult' => [
                    $defaultMinDate,
                    $endOfYear,
                ],
            ],
        ];
    }

    public function testGetScaleTypeUnknownDateRange(): void
    {
        $currentDate = Carbon::today(new \DateTimeZone('UTC'));
        $dateRangeType = \PHP_INT_MAX;

        $this->expectExceptionObject(
            new \InvalidArgumentException(sprintf('Unsupported date range type "%s"', $dateRangeType))
        );

        $this->helper->getScaleType(clone $currentDate, (clone $currentDate)->addDays(5), $dateRangeType);
    }

    /**
     * @dataProvider getScaleTypeDataProvider
     */
    public function testGetScaleType(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        int $dateRangeType,
        string $expectedScaleType
    ): void {
        self::assertEquals($expectedScaleType, $this->helper->getScaleType($startDate, $endDate, $dateRangeType));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getScaleTypeDataProvider(): array
    {
        $currentDate = Carbon::today(new \DateTimeZone('UTC'));

        return [
            'today - hours' => [
                'start' => clone $currentDate,
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_TODAY,
                'expectedScaleType' => 'time',
            ],
            'This Week - days' => [
                'start' => (clone $currentDate)->subDays(2),
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_THIS_WEEK,
                'expectedScaleType' => 'day',
            ],
            'This Month - days' => [
                'start' => (clone $currentDate)->subDays(3),
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_THIS_MONTH,
                'expectedScaleType' => 'day',
            ],
            'This Quarter - days' => [
                'start' => (clone $currentDate)->subDays(3),
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_THIS_QUARTER,
                'expectedScaleType' => 'date',
            ],
            'This Year - days' => [
                'start' => (clone $currentDate)->subDays(3),
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_THIS_YEAR,
                'expectedScaleType' => 'date',
            ],
            'Custom 1 day - hours' => [
                'start' => clone $currentDate,
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_BETWEEN,
                'expectedScaleType' => 'time',
            ],
            'Custom < 31 days - days' => [
                'start' => (clone $currentDate)->subDays(14),
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_BETWEEN,
                'expectedScaleType' => 'day',
            ],
            'Custom 31 days - days' => [
                'start' => (clone $currentDate)->subDays(31),
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_BETWEEN,
                'expectedScaleType' => 'day',
            ],
            'Custom > 31 days and < 53 weeks - weeks' => [
                'start' => (clone $currentDate)->subWeeks(52),
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_BETWEEN,
                'expectedScaleType' => 'date',
            ],
            'Custom - 53 weeks - weeks' => [
                'start' => (clone $currentDate)->subDays(370),
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_BETWEEN,
                'expectedScaleType' => 'date',
            ],
            'Custom > 53 weeks - month' => [
                'start' => (clone $currentDate)->subWeeks(54),
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_BETWEEN,
                'expectedScaleType' => 'month',
            ],
            'Later than: < 31 days - days' => [
                'start' => (clone $currentDate)->subDays(14),
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_MORE_THAN,
                'expectedScaleType' => 'day',
            ],
            'Later than: > 31 days and < 53 weeks - weeks' => [
                'start' => (clone $currentDate)->subWeeks(52),
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_MORE_THAN,
                'expectedScaleType' => 'date',
            ],
            'Later than: > 53 weeks - month' => [
                'start' => (clone $currentDate)->subWeeks(54),
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_MORE_THAN,
                'expectedScaleType' => 'month',
            ],
            'Less than: < 31 days - days' => [
                'start' => (clone $currentDate)->subDays(14),
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_LESS_THAN,
                'expectedScaleType' => 'day',
            ],
            'Less than: > 31 days and <= 53 weeks - weeks' => [
                'start' => (clone $currentDate)->subWeeks(52),
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_LESS_THAN,
                'expectedScaleType' => 'date',
            ],
            'Less than: > 53 weeks - month' => [
                'start' => (clone $currentDate)->subWeeks(54),
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_LESS_THAN,
                'expectedScaleType' => 'month',
            ],
            'All time: 1 day - hours' => [
                'start' => clone $currentDate,
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_ALL_TIME,
                'expectedScaleType' => 'time',
            ],
            'All time: < 31 days - days' => [
                'start' => (clone $currentDate)->subDays(14),
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_ALL_TIME,
                'expectedScaleType' => 'day',
            ],
            'All time: 31 days - days' => [
                'start' => (clone $currentDate)->subDays(31),
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_ALL_TIME,
                'expectedScaleType' => 'day',
            ],
            'All time: > 31 days and < 53 weeks - weeks' => [
                'start' => (clone $currentDate)->subWeeks(52),
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_ALL_TIME,
                'expectedScaleType' => 'date',
            ],
            'All time: > 53 weeks - month' => [
                'start' => (clone $currentDate)->subWeeks(54),
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_ALL_TIME,
                'expectedScaleType' => 'month',
            ],
            'All time: 53 weeks - weeks' => [
                'start' => (clone $currentDate)->subDays(370),
                'end' => clone $currentDate,
                'dateRangeType' => AbstractDateFilterType::TYPE_ALL_TIME,
                'expectedScaleType' => 'date',
            ],
        ];
    }

    public function testGetDateIntervalConfigByScaleTypeUnknownScale(): void
    {
        $scaleType = 'unknown_scale_Type';

        $this->expectExceptionObject(
            new \InvalidArgumentException(sprintf('Unsupported scale "%s"', $scaleType))
        );

        $this->helper->getDateIntervalConfigByScaleType($scaleType);
    }

    /**
     * @dataProvider getDateIntervalConfigByScaleTypeDataProvider
     */
    public function testGetDateIntervalConfigByScaleType(string $scaleType, array $expectedConfig): void
    {
        self::assertEquals($expectedConfig, $this->helper->getDateIntervalConfigByScaleType($scaleType));
    }

    public function getDateIntervalConfigByScaleTypeDataProvider(): array
    {
        return [
            'year' => [
                'scaleType' => 'year',
                [
                    'intervalString' => 'P1Y',
                    'valueStringFormat' => 'Y',
                    'viewType' => 'year',
                ],
            ],
            'month' => [
                'scaleType' => 'month',
                [
                    'intervalString' => 'P1M',
                    'valueStringFormat' => 'Y-m',
                    'viewType' => 'month',
                ],
            ],
            'date' => [
                'scaleType' => 'date',
                [
                    'intervalString' => 'P1W',
                    'valueStringFormat' => 'o-W',
                    'viewType' => 'date',
                ],
            ],
            'day' => [
                'scaleType' => 'day',
                [
                    'intervalString' => 'P1D',
                    'valueStringFormat' => 'Y-m-d',
                    'viewType' => 'day',
                ],
            ],
            'time' => [
                'scaleType' => 'time',
                [
                    'intervalString' => 'PT1H',
                    'valueStringFormat' => 'Y-m-d-H',
                    'viewType' => 'time',
                ],
            ],
        ];
    }
}
