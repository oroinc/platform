<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * This trait can be used by migrations that implement {@see DatabasePlatformAwareInterface}.
 */
trait DatabasePlatformAwareTrait
{
    /** @var AbstractPlatform */
    protected $platform;

    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }
}
