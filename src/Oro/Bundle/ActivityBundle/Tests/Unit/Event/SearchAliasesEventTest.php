<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Event;

use Oro\Bundle\ActivityBundle\Event\SearchAliasesEvent;

class SearchAliasesEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $aliases = ['test'];
        $event = new SearchAliasesEvent($aliases, []);
        $this->assertSame($aliases, $event->getAliases());
        $updatedAliases = array_merge($aliases, ['customEntity']);
        $event->setAliases($updatedAliases);
        $this->assertSame($updatedAliases, $event->getAliases());
    }
}
