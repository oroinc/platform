<?php

namespace Oro\Bundle\SanitizeBundle\Tests\Functional\Environment\Migrations;

use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class TestEntitiesMigrationListener
{
    public function onPostUp(PostMigrationEvent $event): void
    {
        $event->addMigration(new TestEntitiesMigration());
    }
}
