<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

use Doctrine\DBAL\Platforms\AbstractPlatform;

trait DatabasePlatformAwareTrait
{
    /** @var AbstractPlatform */
    protected $platform;

    /**
     * @param AbstractPlatform $platform
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }
}
