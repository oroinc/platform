<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Event;

use Oro\Bundle\ImportExportBundle\Event\DenormalizeEntityEvent;
use Oro\Bundle\UserBundle\Entity\User;

class DenormalizeEntityEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $object = new User();
        $data = ['a' => 'b'];

        $event = new DenormalizeEntityEvent($object, $data);
        $this->assertSame($object, $event->getObject());
        $this->assertSame($data, $event->getData());
    }
}
