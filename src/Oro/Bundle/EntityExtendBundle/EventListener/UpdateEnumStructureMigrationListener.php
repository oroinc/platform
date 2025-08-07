<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityExtendBundle\Migration\Enum\MoveBaseEnumOptionsMigration;
use Oro\Bundle\EntityExtendBundle\Migration\Enum\RefreshExtendEntityCacheMigration;
use Oro\Bundle\EntityExtendBundle\Migration\Enum\UpdateBaseEnumRelatedDataMigration;
use Oro\Bundle\EntityExtendBundle\Migration\Enum\UpdateEnumerableSystemConfigMigration;
use Oro\Bundle\EntityExtendBundle\Migration\Enum\UpdateExtendEntityEnumFieldsMigration;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Update enum structure from relation based to serialized.
 */
class UpdateEnumStructureMigrationListener implements ServiceSubscriberInterface
{
    public function __construct(protected ContainerInterface $container, private iterable $providers)
    {
    }

    /**
     * POST UP event handler
     */
    public function onPostUp(PostMigrationEvent $event): void
    {
        $event->addMigration(new MoveBaseEnumOptionsMigration($this->container));
        $event->addMigration(new UpdateExtendEntityEnumFieldsMigration($this->container));
        $event->addMigration(new UpdateBaseEnumRelatedDataMigration($this->container));
        $event->addMigration(new UpdateEnumerableSystemConfigMigration($this->container, $this->providers));
        $event->addMigration(new RefreshExtendEntityCacheMigration($this->container));
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [];
    }
}
