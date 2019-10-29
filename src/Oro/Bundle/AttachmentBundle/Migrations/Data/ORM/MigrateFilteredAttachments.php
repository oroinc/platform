<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Migration\FilteredAttachmentMigrationService;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Migrate filtered attachments to new directory structure. Applicable for CE
 */
class MigrateFilteredAttachments implements FixtureInterface, ContainerAwareInterface
{
    protected const PREFIX = 'attachment/resize';

    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $migrationService = $this->getMigrationService();
        $migrationService->setManager($manager);
        $migrationService->migrate(static::PREFIX, static::PREFIX);
    }

    /**
     * @return FilteredAttachmentMigrationService
     */
    protected function getMigrationService()
    {
        return $this->container->get('oro_attachment.filtered_attachment_migration');
    }
}
