<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

interface DataStorageExtensionAwareInterface
{
    public function setDataStorageExtension(DataStorageExtension $dataStorageExtension);
}
