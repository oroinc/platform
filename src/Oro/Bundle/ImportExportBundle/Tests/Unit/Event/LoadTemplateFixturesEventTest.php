<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Event;

use Oro\Bundle\ImportExportBundle\Event\LoadTemplateFixturesEvent;

class LoadTemplateFixturesEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $entities = ['a'];

        $event = new LoadTemplateFixturesEvent($entities);
        $this->assertEquals($entities, $event->getEntities());
    }
}
