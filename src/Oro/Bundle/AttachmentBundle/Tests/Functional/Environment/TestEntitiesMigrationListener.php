<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Environment;

use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class TestEntitiesMigrationListener
{
    public function onPostUp(PostMigrationEvent $event): void
    {
        $event->addMigration(new TestEntitiesMigration());
    }
}
