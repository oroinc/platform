<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * This interface should be implemented by migrations that depend on a database platform.
 */
interface DatabasePlatformAwareInterface
{
    public function setDatabasePlatform(AbstractPlatform $platform);
}
