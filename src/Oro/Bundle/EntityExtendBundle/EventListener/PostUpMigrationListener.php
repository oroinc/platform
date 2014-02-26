<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityExtendBundle\Extend\Schema\UpdateExtendConfigMigration;
use Oro\Bundle\InstallerBundle\Migrations\Event\PostMigrationEvent;

class PostUpMigrationListener
{
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(new UpdateExtendConfigMigration());
    }
}
