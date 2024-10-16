<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension\TestExtension;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension\TestExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class MigrationWithTestExtension implements
    Migration,
    TestExtensionAwareInterface,
    DatabasePlatformAwareInterface,
    NameGeneratorAwareInterface,
    LoggerAwareInterface
{
    private ?TestExtension $testExtension = null;
    private ?AbstractPlatform $platform = null;
    private ?DbIdentifierNameGenerator $nameGenerator = null;
    private ?LoggerInterface $logger = null;

    #[\Override]
    public function setTestExtension(TestExtension $testExtension): void
    {
        $this->testExtension = $testExtension;
    }

    public function getTestExtension(): ?TestExtension
    {
        return $this->testExtension;
    }

    #[\Override]
    public function setDatabasePlatform(AbstractPlatform $platform): void
    {
        $this->platform = $platform;
    }

    public function getDatabasePlatform(): ?AbstractPlatform
    {
        return $this->platform;
    }

    #[\Override]
    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator): void
    {
        $this->nameGenerator = $nameGenerator;
    }

    public function getNameGenerator(): ?DbIdentifierNameGenerator
    {
        return $this->nameGenerator;
    }

    #[\Override]
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
    }
}
