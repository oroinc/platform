<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * DatabasePlatformAwareInterface should be implemented by extensions that depends on a database platform.
 */
interface DatabasePlatformAwareInterface
{
    /**
     * Sets the database platform
     *
     * @param AbstractPlatform $platform
     */
    public function setDatabasePlatform(AbstractPlatform $platform);
}
