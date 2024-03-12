<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

/**
 * This interface should be implemented by migrations that depend on {@see RenameExtension}.
 */
interface RenameExtensionAwareInterface
{
    public function setRenameExtension(RenameExtension $renameExtension);
}
