<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Tools;

use Oro\Bundle\CronBundle\Tests\Unit\Stub\ScheduleIntervalStub;
use Oro\Bundle\CronBundle\Tools\ScheduleHelper;
use PHPUnit\Framework\TestCase;

class ScheduleHelperTest extends TestCase
{
    /**
     * @dataProvider scheduleDataProvider
     */
    public function testHasActiveSchedule(iterable $schedules, bool $expected): void
    {
        $this->assertEquals($expected, ScheduleHelper::hasActiveSchedule($schedules));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function scheduleDataProvider(): \Generator
    {
        yield [
            [],
            true
        ];

        yield [
            [
                (new ScheduleIntervalStub())
                    ->setActiveAt(new \DateTime('now + 1 day', new \DateTimeZone('UTC')))
            ],
            false
        ];

        yield [
            [
                (new ScheduleIntervalStub())
                    ->setActiveAt(new \DateTime('now - 1 day', new \DateTimeZone('UTC')))
            ],
            true
        ];

        yield [
            [
                (new ScheduleIntervalStub())
                    ->setDeactivateAt(new \DateTime('now + 1 day', new \DateTimeZone('UTC')))
            ],
            true
        ];

        yield [
            [
                (new ScheduleIntervalStub())
                    ->setDeactivateAt(new \DateTime('now - 1 day', new \DateTimeZone('UTC')))
            ],
            false
        ];

        yield [
            [
                (new ScheduleIntervalStub())
                    ->setActiveAt(new \DateTime('now - 1 day', new \DateTimeZone('UTC')))
                    ->setDeactivateAt(new \DateTime('now + 1 day', new \DateTimeZone('UTC')))
            ],
            true
        ];

        yield [
            [
                (new ScheduleIntervalStub())
                    ->setActiveAt(new \DateTime('now - 2 day', new \DateTimeZone('UTC')))
                    ->setDeactivateAt(new \DateTime('now - 1 day', new \DateTimeZone('UTC')))
            ],
            false
        ];

        yield [
            [
                (new ScheduleIntervalStub())
                    ->setActiveAt(new \DateTime('now - 2 day', new \DateTimeZone('UTC')))
                    ->setDeactivateAt(new \DateTime('now - 1 day', new \DateTimeZone('UTC'))),
                (new ScheduleIntervalStub())
                    ->setActiveAt(new \DateTime('now + 1 day', new \DateTimeZone('UTC')))
                    ->setDeactivateAt(new \DateTime('now + 2 day', new \DateTimeZone('UTC')))
            ],
            false
        ];

        yield [
            [
                (new ScheduleIntervalStub())
                    ->setActiveAt(new \DateTime('now - 3 day', new \DateTimeZone('UTC')))
                    ->setDeactivateAt(new \DateTime('now - 2 day', new \DateTimeZone('UTC'))),
                (new ScheduleIntervalStub())
                    ->setActiveAt(new \DateTime('now - 1 day', new \DateTimeZone('UTC')))
                    ->setDeactivateAt(new \DateTime('now + 1 day', new \DateTimeZone('UTC'))),
                (new ScheduleIntervalStub())
                    ->setActiveAt(new \DateTime('now +2 day', new \DateTimeZone('UTC')))
                    ->setDeactivateAt(new \DateTime('now + 3 day', new \DateTimeZone('UTC')))
            ],
            true
        ];
    }
}
