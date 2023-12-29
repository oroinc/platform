<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

/**
 * This trait can be used by migrations that implement {@see DataStorageExtensionAwareInterface}.
 */
trait DataStorageExtensionAwareTrait
{
    private DataStorageExtension $dataStorageExtension;

    public function setDataStorageExtension(DataStorageExtension $dataStorageExtension): void
    {
        $this->dataStorageExtension = $dataStorageExtension;
    }
}
