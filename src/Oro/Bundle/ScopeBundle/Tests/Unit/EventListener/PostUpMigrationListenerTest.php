<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\EventListener;

use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;
use Oro\Bundle\ScopeBundle\EventListener\PostUpMigrationListener;
use Oro\Bundle\ScopeBundle\Migration\AddCommentToRoHashManager;
use Oro\Bundle\ScopeBundle\Migration\Schema\AddTriggerToRowHashColumn;
use Oro\Bundle\ScopeBundle\Migration\Schema\UpdateScopeRowHashColumn;
use PHPUnit\Framework\TestCase;

class PostUpMigrationListenerTest extends TestCase
{
    /**
     * @var AddCommentToRoHashManager
     */
    private $manager;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(AddCommentToRoHashManager::class);
    }

    public function testInstalled(): void
    {
        $listener = new PostUpMigrationListener($this->manager, 'true');

        $event = $this->createMock(PostMigrationEvent::class);
        $event->expects($this->once())
            ->method('addMigration')
            ->with($this->isInstanceOf(UpdateScopeRowHashColumn::class));

        $listener->onPostUp($event);
    }

    public function testNotInstalled(): void
    {
        $listener = new PostUpMigrationListener($this->manager, null);

        $event = $this->createMock(PostMigrationEvent::class);

        $event->expects($this->once())
            ->method('addMigration')
            ->with($this->isInstanceOf(AddTriggerToRowHashColumn::class));

        $listener->onPostUp($event);
    }
}
