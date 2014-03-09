<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class TestExtension implements DatabasePlatformAwareInterface, NameGeneratorAwareInterface
{
    protected $platform;

    protected $nameGenerator;

    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    public function getDatabasePlatform()
    {
        return $this->platform;
    }

    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
    }

    public function getNameGenerator()
    {
        return $this->nameGenerator;
    }
}
