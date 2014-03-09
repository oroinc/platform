<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;

class TestExtension implements DatabasePlatformAwareInterface
{
    protected $platform;

    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    public function getDatabasePlatform()
    {
        return $this->platform;
    }
}
