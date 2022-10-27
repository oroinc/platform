<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Environment;

use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

/**
 * Adds several extended attributes to TestActivityTarget entity to use in functional tests.
 * @see \Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeData
 */
class TestEntitiesMigrationListener
{
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(new AddAttributesToTestActivityTargetMigration());
    }
}
