<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Migration\FilteredAttachmentMigrationServiceInterface;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Migrate filtered attachments to new directory structure. Applicable for CE
 */
class MigrateFilteredAttachments implements FixtureInterface, ContainerAwareInterface, VersionedFixtureInterface
{
    protected const PREFIX = 'attachment/resize';

    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getVersion(): string
    {
        return '1.0';
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $migrationService = $this->getMigrationService();
        $migrationService->setManager($manager);
        $migrationService->migrate(static::PREFIX, static::PREFIX);
    }

    protected function getMigrationService(): FilteredAttachmentMigrationServiceInterface
    {
        return $this->container->get('oro_attachment.filtered_attachment_migration');
    }
}
