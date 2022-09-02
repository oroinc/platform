<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Event;

use Oro\Bundle\ImportExportBundle\Event\NormalizeEntityEvent;
use Oro\Bundle\UserBundle\Entity\User;

class NormalizeEntityEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent(): void
    {
        $object = new User();
        $result = ['username' => 'user'];
        $event = new NormalizeEntityEvent($object, $result);

        self::assertSame($object, $event->getObject());
        self::assertEquals($result, $event->getResult());
    }

    public function testSetResultFieldValue(): void
    {
        $object = new User();
        $result = ['username' => 'user'];
        $event = new NormalizeEntityEvent($object, $result);

        $event->setResultFieldValue('sampleField', 'sampleValue');
        self::assertEquals('sampleValue', $event->getResult()['sampleField']);
    }
}
