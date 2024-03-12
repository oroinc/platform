<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * This trait can be used by migrations that implement {@see DatabasePlatformAwareInterface}.
 */
trait DatabasePlatformAwareTrait
{
    private AbstractPlatform $platform;

    public function setDatabasePlatform(AbstractPlatform $platform): void
    {
        $this->platform = $platform;
    }
}
