<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CronBundle\Entity\ScheduleIntervalInterface;
use Oro\Bundle\CronBundle\Tests\Unit\Stub\ScheduleIntervalsHolderStub;
use Oro\Bundle\CronBundle\Tests\Unit\Stub\ScheduleIntervalStub;
use Oro\Bundle\CronBundle\Validator\Constraints\ScheduleIntervalsIntersection;
use Oro\Bundle\CronBundle\Validator\Constraints\ScheduleIntervalsIntersectionValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ScheduleIntervalsIntersectionValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new ScheduleIntervalsIntersectionValidator();
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $this->constraint = new ScheduleIntervalsIntersection();
        $this->propertyPath = null;

        return parent::createContext();
    }

    /**
     * @param array|ScheduleIntervalInterface[] $collection
     *
     * @return ScheduleIntervalInterface[]
     */
    private function normalizeCollection(array $collection)
    {
        $collection = array_map(function ($dates) {
            return $this->normalizeSingleDateData($dates);
        }, $collection);

        return $collection;
    }

    /**
     * @param array $dates
     *
     * @return ScheduleIntervalInterface
     */
    private function normalizeSingleDateData(array $dates)
    {
        $start = (null === $dates[0]) ? null : new \DateTime($dates[0]);
        $end = (null === $dates[1]) ? null : new \DateTime($dates[1]);

        $scheduleInterval = new ScheduleIntervalStub();
        $scheduleInterval->setActiveAt($start);
        $scheduleInterval->setDeactivateAt($end);

        return $scheduleInterval;
    }

    /**
     * @dataProvider validateSuccessDataProvider
     */
    public function testValidateSuccess(array $collection)
    {
        $collection = $this->normalizeCollection($collection);
        $holder = (new ScheduleIntervalsHolderStub())->setSchedules($collection);

        $date = reset($collection);
        $date->setHolder($holder);

        $this->validator->validate($date, $this->constraint);

        $this->assertNoViolation();
    }

    /**
     * @return array
     */
    public function validateSuccessDataProvider()
    {
        return [
            'without intersections'                                        => [
                'collection'    => [
                    ['2016-01-01', '2016-01-31'],
                    ['2016-02-01', '2016-03-01'],
                ],
                'intersections' => []
            ],
            'without intersections, left=null'                             => [
                'collection'    => [
                    [null, '2016-01-31'],
                    ['2016-02-01', '2016-03-01'],
                ],
                'intersections' => []
            ],
            'without intersections, right = null'                          => [
                'collection'    => [
                    ['2016-01-01', '2016-01-31'],
                    ['2016-02-01', null],
                ],
                'intersections' => []
            ],
            'without intersections, right = null and left = null'          => [
                'collection'    => [
                    [null, '2016-01-31'],
                    ['2016-02-01', null],
                ],
                'intersections' => []
            ],
            'without intersections, right = null and left = null(inverse)' => [
                'collection'    => [
                    ['2016-02-01', null],
                    [null, '2016-01-03'],
                ],
                'intersections' => []
            ],
        ];
    }

    /**
     * @dataProvider validateFailDataProvider
     */
    public function testValidateFail(array $collection)
    {
        $collection = $this->normalizeCollection($collection);
        $holder = (new ScheduleIntervalsHolderStub())->setSchedules($collection);

        $date = reset($collection);
        $date->setHolder($holder);

        $this->validator->validate($date, $this->constraint);

        $this->buildViolation($this->constraint->message)
            ->atPath('')
            ->assertRaised();
    }

    /**
     * @return array
     */
    public function validateFailDataProvider()
    {
        return [
            'without intersections, left = null and right = null' => [
                'collection' => [
                    [null, '2016-02-01'],
                    ['2016-01-15', null],
                ]
            ],

            'intersects'                    => [
                'collection' => [
                    ['2016-01-01', '2016-02-01'],
                    ['2016-01-15', '2016-03-01'],
                ]
            ],
            'intersects, right = null'      => [
                'collection' => [
                    ['2016-01-01', '2016-02-01'],
                    ['2016-01-15', null],
                ]
            ],
            'intersects, both right = null' => [
                'collection' => [
                    ['2016-01-01', null],
                    ['2016-01-15', null],
                ]
            ],
            'intersects, left = null'       => [
                'collection' => [
                    [null, '2016-02-01'],
                    ['2016-01-15', '2016-03-01'],
                ]
            ],

            'contains'               => [
                'collection' => [
                    ['2016-01-01', '2016-04-01'],
                    ['2016-02-01', '2016-03-01'],
                ]
            ],
            'contains, left = null'  => [
                'collection' => [
                    [null, '2016-04-01'],
                    ['2016-02-01', '2016-03-01'],
                ]
            ],
            'contains, right = null' => [
                'collection' => [
                    ['2016-01-01', null],
                    ['2016-02-01', '2016-03-01'],
                ]
            ],
            'contains, all null'     => [
                'collection' => [
                    [null, null],
                    ['2016-01-01', '2016-01-02'],
                ]
            ]
        ];
    }

    public function testNotPriceListScheduleValue()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(12, new ScheduleIntervalsIntersection());
    }
}
