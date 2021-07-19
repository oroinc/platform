<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Event;

use Oro\Bundle\BatchBundle\Event\InvalidItemEvent;

class InvalidItemEventTest extends \PHPUnit\Framework\TestCase
{
    public function testAccessors(): void
    {
        $event = new InvalidItemEvent(
            \stdClass::class,
            'No special reason.',
            ['%param%' => 'Item1'],
            ['foo' => 'baz']
        );

        self::assertEquals(\stdClass::class, $event->getClass());
        self::assertEquals('No special reason.', $event->getReason());
        self::assertEquals(['%param%' => 'Item1'], $event->getReasonParameters());
        self::assertEquals(['foo' => 'baz'], $event->getItem());
    }
}
