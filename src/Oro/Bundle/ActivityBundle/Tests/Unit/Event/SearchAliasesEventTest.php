<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Event;

use Oro\Bundle\ActivityBundle\Event\SearchAliasesEvent;
use PHPUnit\Framework\TestCase;

class SearchAliasesEventTest extends TestCase
{
    public function testEvent(): void
    {
        $aliases = ['test'];
        $event = new SearchAliasesEvent($aliases, []);
        $this->assertSame($aliases, $event->getAliases());
        $updatedAliases = array_merge($aliases, ['customEntity']);
        $event->setAliases($updatedAliases);
        $this->assertSame($updatedAliases, $event->getAliases());
    }
}
