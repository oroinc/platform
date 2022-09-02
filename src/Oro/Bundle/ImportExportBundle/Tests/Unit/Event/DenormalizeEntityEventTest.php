<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Event;

use Oro\Bundle\ImportExportBundle\Event\DenormalizeEntityEvent;
use Oro\Bundle\UserBundle\Entity\User;

class DenormalizeEntityEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent(): void
    {
        $object = new User();
        $data = ['a' => 'b'];

        $event = new DenormalizeEntityEvent($object, $data);
        self::assertSame($object, $event->getObject());
        self::assertSame($data, $event->getData());
    }

    public function testMarkAsSkipped(): void
    {
        $object = new \stdClass();
        $event = new DenormalizeEntityEvent($object, []);

        $event->markAsSkipped('sampleField');
        self::assertTrue($event->isFieldSkipped('sampleField'));

        $event->markAsSkipped('sampleField', false);
        self::assertFalse($event->isFieldSkipped('sampleField'));
    }
}
