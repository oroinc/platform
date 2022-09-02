<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActivityBundle\Event\SearchAliasesEvent;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\EventListener\SearchAliasesListener;

class SearchAliasesListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var SearchAliasesListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new SearchAliasesListener();
    }

    public function testAddEmailAliasEventSkipped()
    {
        $event = new SearchAliasesEvent([], []);
        $this->listener->addEmailAliasEvent($event);
        $this->assertEquals([], $event->getAliases());
    }

    public function testAddEmailAliasEvent()
    {
        $event = new SearchAliasesEvent([], [Email::class]);
        $this->listener->addEmailAliasEvent($event);
        $this->assertEquals(['oro_email'], $event->getAliases());
    }
}
