<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Oro\Bundle\WorkflowBundle\EventListener\PostUpMigrationListener;
use Oro\Bundle\WorkflowBundle\Migrations\Schema\UpdateWorkflowItemFieldsMigration;

class PostUpMigrationListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnPostUp()
    {
        $event = $this->getMockBuilder('Oro\Bundle\MigrationBundle\Event\PostMigrationEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('addMigration')
            ->with($this->isInstanceOf(UpdateWorkflowItemFieldsMigration::class), true);

        $listener = new PostUpMigrationListener();
        $listener->onPostUp($event);
    }
}
