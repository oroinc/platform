<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Event;

use Oro\Bundle\ImportExportBundle\Event\NormalizeEntityEvent;
use Oro\Bundle\UserBundle\Entity\User;

class NormalizeEntityEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $object = new User();
        $result = ['username' => 'user'];
        $event = new NormalizeEntityEvent($object, $result);

        $this->assertSame($object, $event->getObject());
        $this->assertEquals($result, $event->getResult());
    }
}
