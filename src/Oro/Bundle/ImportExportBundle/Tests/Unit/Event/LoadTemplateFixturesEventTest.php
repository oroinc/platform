<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Event;

use Oro\Bundle\ImportExportBundle\Event\LoadTemplateFixturesEvent;
use PHPUnit\Framework\TestCase;

class LoadTemplateFixturesEventTest extends TestCase
{
    public function testEvent(): void
    {
        $entities = ['a'];

        $event = new LoadTemplateFixturesEvent($entities);
        $this->assertEquals($entities, $event->getEntities());
    }
}
