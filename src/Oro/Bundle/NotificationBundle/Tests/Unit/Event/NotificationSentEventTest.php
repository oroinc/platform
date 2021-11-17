<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Event;

use Oro\Bundle\NotificationBundle\Event\NotificationSentEvent;
use Symfony\Component\Mime\RawMessage;

class NotificationSentEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters(): void
    {
        $message = new RawMessage('sample body');
        $event = new NotificationSentEvent($message, 1, 'sample_type');

        self::assertSame($message, $event->getMessage());
        self::assertSame(1, $event->getSentCount());
        self::assertSame('sample_type', $event->getType());
    }
}
