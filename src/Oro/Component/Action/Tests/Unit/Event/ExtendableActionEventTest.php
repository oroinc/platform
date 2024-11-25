<?php

namespace Oro\Component\Action\Tests\Unit\Event;

use Oro\Component\Action\Event\ExtendableActionEvent;
use Oro\Component\Action\Model\AbstractStorage;
use PHPUnit\Framework\TestCase;

class ExtendableActionEventTest extends TestCase
{
    public function testGetContextWithNull(): void
    {
        $event = new ExtendableActionEvent();

        $this->assertNull($event->getData());
    }

    public function testGetContextWithNonNullValue(): void
    {
        $context = $this->createMock(AbstractStorage::class);
        $event = new ExtendableActionEvent($context);

        $this->assertSame($context, $event->getData());
    }
}
