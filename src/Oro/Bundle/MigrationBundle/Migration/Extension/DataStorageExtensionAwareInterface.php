<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

/**
 * This interface should be implemented by migrations that depend on {@see DataStorageExtension}.
 */
interface DataStorageExtensionAwareInterface
{
    public function setDataStorageExtension(DataStorageExtension $dataStorageExtension);
}
