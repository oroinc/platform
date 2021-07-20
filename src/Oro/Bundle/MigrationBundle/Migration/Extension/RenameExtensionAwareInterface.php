<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

/**
 * RenameExtensionAwareInterface should be implemented by migrations that depends on a RenameExtension.
 */
interface RenameExtensionAwareInterface
{
    /**
     * Sets the RenameExtension
     */
    public function setRenameExtension(RenameExtension $renameExtension);
}
