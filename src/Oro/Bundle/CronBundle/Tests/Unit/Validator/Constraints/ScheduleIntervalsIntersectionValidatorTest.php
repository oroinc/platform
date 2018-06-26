<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CronBundle\Entity\ScheduleIntervalInterface;
use Oro\Bundle\CronBundle\Form\Type\ScheduleIntervalType;
use Oro\Bundle\CronBundle\Tests\Unit\Stub\ScheduleIntervalsHolderStub;
use Oro\Bundle\CronBundle\Tests\Unit\Stub\ScheduleIntervalStub;
use Oro\Bundle\CronBundle\Validator\Constraints\ScheduleIntervalsIntersection;
use Oro\Bundle\CronBundle\Validator\Constraints\ScheduleIntervalsIntersectionValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilder;

class ScheduleIntervalsIntersectionValidatorTest extends \PHPUnit\Framework\TestCase
{
    const MESSAGE = 'oro.cron.validators.schedule_intervals_overlap.message';

    /**
     * @dataProvider validateSuccessDataProvider
     *
     * @param array $collection
     */
    public function testValidateSuccess(array $collection)
    {
        $constraint = new ScheduleIntervalsIntersection();
        $context = $this->getContextMock();

        $context->expects($this->never())
            ->method('buildViolation');
        $collection = $this->normalizeCollection($collection);

        $validator = new ScheduleIntervalsIntersectionValidator();
        $validator->initialize($context);

        $holder = (new ScheduleIntervalsHolderStub())->setSchedules($collection);
        $date = reset($collection);
        $date->setHolder($holder);
        $validator->validate($date, $constraint);
    }

    /**
     * @dataProvider validateFailDataProvider
     *
     * @param array $collection
     */
    public function testValidateOnApiForm(array $collection)
    {
        $constraint = new ScheduleIntervalsIntersection();
        $context = $this->getContextMock();
        $builder = $this->createMock(ConstraintViolationBuilder::class);
        $formMock = $this->createMock(\Symfony\Component\Form\Form::class);
        $config = $this->createMock(\Symfony\Component\Form\FormConfigInterface::class);

        $formMock
            ->expects(static::once())
            ->method('getConfig')
            ->willReturn($config);

        $config
            ->expects(static::once())
            ->method('hasOption')
            ->with('api_context')
            ->willReturn(true);

        $builder->expects($this->any())
            ->method('addViolation')
            ->willReturn($builder);

        $context
            ->expects(static::once())
            ->method('getRoot')
            ->willReturn($formMock);

        $context->expects($this->any())
            ->method('buildViolation')
            ->with(self::MESSAGE, [])
            ->willReturn($builder);

        $builder->expects($this->never())
            ->method('atPath')
            ->with(ScheduleIntervalType::ACTIVE_AT_FIELD)
            ->willReturnSelf();

        $collection = $this->normalizeCollection($collection);

        $validator = new ScheduleIntervalsIntersectionValidator();
        $validator->initialize($context);
        $holder = (new ScheduleIntervalsHolderStub())->setSchedules($collection);

        $date = reset($collection);
        $date->setHolder($holder);
        $validator->validate($date, $constraint);
    }

    /**
     * @return array
     */
    public function validateSuccessDataProvider()
    {
        return [
            'without intersections' => [
                'collection' => [
                    ['2016-01-01', '2016-01-31'],
                    ['2016-02-01', '2016-03-01'],
                ],
                'intersections' => []
            ],
            'without intersections, left=null' => [
                'collection' => [
                    [null, '2016-01-31'],
                    ['2016-02-01', '2016-03-01'],
                ],
                'intersections' => []
            ],
            'without intersections, right = null' => [
                'collection' => [
                    ['2016-01-01', '2016-01-31'],
                    ['2016-02-01', null],
                ],
                'intersections' => []
            ],
            'without intersections, right = null and left = null' => [
                'collection' => [
                    [null, '2016-01-31'],
                    ['2016-02-01', null],
                ],
                'intersections' => []
            ],
            'without intersections, right = null and left = null(inverse)' => [
                'collection' => [
                    ['2016-02-01', null],
                    [null, '2016-01-03'],
                ],
                'intersections' => []
            ],
        ];
    }

    /**
     * @dataProvider validateFailDataProvider
     *
     * @param array $collection
     */
    public function testValidateFail(array $collection)
    {
        $constraint = new ScheduleIntervalsIntersection();
        $context = $this->getContextMock();
        $builder = $this->createMock(ConstraintViolationBuilder::class);

        $builder->expects($this->any())
            ->method('addViolation')
            ->willReturn($builder);

        $context->expects($this->any())
            ->method('buildViolation')
            ->with(self::MESSAGE, [])
            ->willReturn($builder);

        $builder->expects($this->once())
            ->method('atPath')
            ->with(ScheduleIntervalType::ACTIVE_AT_FIELD)
            ->willReturnSelf();

        $collection = $this->normalizeCollection($collection);

        $validator = new ScheduleIntervalsIntersectionValidator();
        $validator->initialize($context);
        $holder = (new ScheduleIntervalsHolderStub())->setSchedules($collection);

        $date = reset($collection);
        $date->setHolder($holder);
        $validator->validate($date, $constraint);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNotPriceListScheduleValue()
    {
        $constraint = new ScheduleIntervalsIntersection();
        $context = $this->getContextMock();

        $validator = new ScheduleIntervalsIntersectionValidator();
        $validator->initialize($context);
        /** @var array $notIterable */
        $notIterable = 12;
        $validator->validate($notIterable, $constraint);
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

            'intersects' => [
                'collection' => [
                    ['2016-01-01', '2016-02-01'],
                    ['2016-01-15', '2016-03-01'],
                ]
            ],
            'intersects, right = null' => [
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
            'intersects, left = null' => [
                'collection' => [
                    [null, '2016-02-01'],
                    ['2016-01-15', '2016-03-01'],
                ]
            ],

            'contains' => [
                'collection' => [
                    ['2016-01-01', '2016-04-01'],
                    ['2016-02-01', '2016-03-01'],
                ]
            ],
            'contains, left = null' => [
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
            'contains, all null' => [
                'collection' => [
                    [null, null],
                    ['2016-01-01', '2016-01-02'],
                ]
            ]
        ];
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface $context
     */
    protected function getContextMock()
    {
        return $this->createMock(ExecutionContextInterface::class);
    }

    /**
     * @param array|ScheduleIntervalInterface[] $collection
     * @return ScheduleIntervalInterface[]
     */
    protected function normalizeCollection(array $collection)
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
    protected function normalizeSingleDateData(array $dates)
    {
        $start = (null === $dates[0]) ? null : new \DateTime($dates[0]);
        $end = (null === $dates[1]) ? null : new \DateTime($dates[1]);

        $scheduleInterval = new ScheduleIntervalStub();
        $scheduleInterval->setActiveAt($start);
        $scheduleInterval->setDeactivateAt($end);

        return $scheduleInterval;
    }
}
