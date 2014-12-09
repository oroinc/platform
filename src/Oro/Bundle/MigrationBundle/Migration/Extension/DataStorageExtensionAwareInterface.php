<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

interface DataStorageExtensionAwareInterface
{
    /**
     * @param DataStorageExtension $storage
     */
    public function setDataStorageExtension(DataStorageExtension $storage);
}
