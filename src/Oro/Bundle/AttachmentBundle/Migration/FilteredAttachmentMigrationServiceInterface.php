<?php

namespace Oro\Bundle\AttachmentBundle\Migration;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Migrate filtered attachments to new directory structure
 * @internal
 */
interface FilteredAttachmentMigrationServiceInterface
{
    public function setManager(EntityManagerInterface $manager);

    public function migrate(string $fromPrefix, string $toPrefix);

    /**
     * @param string $prefix
     * @param array|string[] $subfolders
     */
    public function clear(string $prefix, array $subfolders);
}
