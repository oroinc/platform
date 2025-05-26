<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActivityBundle\Event\SearchAliasesEvent;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\EventListener\SearchAliasesListener;
use PHPUnit\Framework\TestCase;

class SearchAliasesListenerTest extends TestCase
{
    private SearchAliasesListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new SearchAliasesListener();
    }

    public function testAddEmailAliasEventSkipped(): void
    {
        $event = new SearchAliasesEvent([], []);
        $this->listener->addEmailAliasEvent($event);
        $this->assertEquals([], $event->getAliases());
    }

    public function testAddEmailAliasEvent(): void
    {
        $event = new SearchAliasesEvent([], [Email::class]);
        $this->listener->addEmailAliasEvent($event);
        $this->assertEquals(['oro_email'], $event->getAliases());
    }
}
