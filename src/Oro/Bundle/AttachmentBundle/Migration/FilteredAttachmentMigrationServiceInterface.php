<?php

namespace Oro\Bundle\AttachmentBundle\Migration;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Migrate filtered attachments to new directory structure
 * @internal
 */
interface FilteredAttachmentMigrationServiceInterface
{
    /**
     * @param EntityManagerInterface $manager
     */
    public function setManager(EntityManagerInterface $manager);

    /**
     * @param string $fromPrefix
     * @param string $toPrefix
     */
    public function migrate(string $fromPrefix, string $toPrefix);

    /**
     * @param string $prefix
     * @param array|string[] $subfolders
     */
    public function clear(string $prefix, array $subfolders);
}
