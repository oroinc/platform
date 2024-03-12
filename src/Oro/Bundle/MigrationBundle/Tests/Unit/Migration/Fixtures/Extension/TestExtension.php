<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class TestExtension implements DatabasePlatformAwareInterface, NameGeneratorAwareInterface, LoggerAwareInterface
{
    private ?AbstractPlatform $platform = null;
    private ?DbIdentifierNameGenerator $nameGenerator = null;
    private ?LoggerInterface $logger = null;

    public function setDatabasePlatform(AbstractPlatform $platform): void
    {
        $this->platform = $platform;
    }

    public function getDatabasePlatform(): ?AbstractPlatform
    {
        return $this->platform;
    }

    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator): void
    {
        $this->nameGenerator = $nameGenerator;
    }

    public function getNameGenerator(): ?DbIdentifierNameGenerator
    {
        return $this->nameGenerator;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }
}
