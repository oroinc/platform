<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\EventListener;

use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;
use Oro\Bundle\ScopeBundle\EventListener\PostUpMigrationListener;
use Oro\Bundle\ScopeBundle\Migration\AddCommentToRowHashManager;
use Oro\Bundle\ScopeBundle\Migration\Schema\AddTriggerToRowHashColumn;
use Oro\Bundle\ScopeBundle\Migration\Schema\UpdateScopeRowHashColumn;
use PHPUnit\Framework\TestCase;

class PostUpMigrationListenerTest extends TestCase
{
    private AddCommentToRowHashManager $manager;

    private ApplicationState $applicationState;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(AddCommentToRowHashManager::class);
        $this->applicationState = $this->createMock(ApplicationState::class);
    }

    public function testInstalled(): void
    {
        $this->applicationState->method('isInstalled')
            ->willReturn(true);
        $listener = new PostUpMigrationListener($this->manager, $this->applicationState);

        $event = $this->createMock(PostMigrationEvent::class);
        $event->expects($this->once())
            ->method('addMigration')
            ->with($this->isInstanceOf(UpdateScopeRowHashColumn::class));

        $listener->onPostUp($event);
    }

    public function testNotInstalled(): void
    {
        $this->applicationState->method('isInstalled')
            ->willReturn(false);
        $listener = new PostUpMigrationListener($this->manager, $this->applicationState);

        $event = $this->createMock(PostMigrationEvent::class);

        $event->expects($this->once())
            ->method('addMigration')
            ->with($this->isInstanceOf(AddTriggerToRowHashColumn::class));

        $listener->onPostUp($event);
    }
}
