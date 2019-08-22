<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
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
        $migrationService = $this->container->get('oro_attachment.filtered_attachment_migration');

        $fileIds = $migrationService->migrate(self::PREFIX, self::PREFIX);
        $migrationService->clear(self::PREFIX, $fileIds);
    }
}
