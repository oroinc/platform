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
    protected function createValidator(): ScheduleIntervalsIntersectionValidator
    {
        return new ScheduleIntervalsIntersectionValidator();
    }

    /**
     * @param ScheduleIntervalInterface[] $collection
     *
     * @return ScheduleIntervalInterface[]
     */
    private function normalizeCollection(array $collection): array
    {
        return array_map(function (array $dates) {
            return $this->normalizeSingleDateData($dates);
        }, $collection);
    }

    private function normalizeSingleDateData(array $dates): ScheduleIntervalInterface
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
    public function testValidateSuccess(array $collection): void
    {
        $collection = $this->normalizeCollection($collection);
        $holder = (new ScheduleIntervalsHolderStub())->setSchedules($collection);

        $date = reset($collection);
        $date->setHolder($holder);

        $constraint = new ScheduleIntervalsIntersection();
        $this->validator->validate($date, $constraint);
        $this->assertNoViolation();
    }

    public function validateSuccessDataProvider(): array
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
    public function testValidateFail(array $collection): void
    {
        $collection = $this->normalizeCollection($collection);
        $holder = (new ScheduleIntervalsHolderStub())->setSchedules($collection);

        $date = reset($collection);
        $date->setHolder($holder);

        $constraint = new ScheduleIntervalsIntersection();
        $this->validator->validate($date, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function validateFailDataProvider(): array
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

    public function testNotPriceListScheduleValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(12, new ScheduleIntervalsIntersection());
    }
}
