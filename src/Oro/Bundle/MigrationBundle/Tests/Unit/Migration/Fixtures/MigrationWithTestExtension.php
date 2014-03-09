<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension\TestExtension;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension\TestExtensionAwareInterface;

class MigrationWithTestExtension extends Migration implements
    TestExtensionAwareInterface,
    DatabasePlatformAwareInterface,
    NameGeneratorAwareInterface
{
    protected $testExtension;

    protected $platform;

    protected $nameGenerator;

    public function setTestExtension(TestExtension $testExtension)
    {
        $this->testExtension = $testExtension;
    }

    public function getTestExtension()
    {
        return $this->testExtension;
    }

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

    public function up(Schema $schema, QueryBag $queries)
    {
    }
}
