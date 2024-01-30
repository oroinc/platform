<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

/**
 * This trait can be used by migrations that implement {@see RenameExtensionAwareInterface}.
 */
trait RenameExtensionAwareTrait
{
    private RenameExtension $renameExtension;

    public function setRenameExtension(RenameExtension $renameExtension): void
    {
        $this->renameExtension = $renameExtension;
    }
}
