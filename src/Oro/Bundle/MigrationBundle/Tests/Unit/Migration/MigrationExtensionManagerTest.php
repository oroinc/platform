<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Oro\Bundle\MigrationBundle\Migration\MigrationExtensionManager;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension\AnotherNoAwareInterfaceExtension;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension\AnotherTestExtension;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension\InvalidAwareInterfaceExtension;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension\NoAwareInterfaceExtension;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension\TestExtension;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension\TestExtensionDepended;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\MigrationWithTestExtension;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\MigrationWithTestExtensionDepended;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MigrationExtensionManagerTest extends TestCase
{
    public function testValidExtension(): void
    {
        $migration = new MigrationWithTestExtension();
        $extension = new TestExtension();
        $platform = new MySQLPlatform();
        $nameGenerator = new DbIdentifierNameGenerator();
        $logger = $this->createMock(LoggerInterface::class);

        $manager = new MigrationExtensionManager();
        $manager->addExtension('test', $extension);
        $manager->applyExtensions($migration);

        self::assertSame($extension, $migration->getTestExtension());
        self::assertNull($extension->getDatabasePlatform());
        self::assertNull($extension->getNameGenerator());
        self::assertNull($extension->getLogger());

        $manager->setDatabasePlatform($platform);
        self::assertSame($platform, $extension->getDatabasePlatform());

        $manager->setNameGenerator($nameGenerator);
        self::assertSame($nameGenerator, $extension->getNameGenerator());

        $manager->setLogger($logger);
        self::assertSame($logger, $extension->getLogger());

        self::assertNull($migration->getDatabasePlatform());
        self::assertNull($migration->getNameGenerator());
        self::assertNull($migration->getLogger());
        $manager->applyExtensions($migration);
        self::assertSame($platform, $migration->getDatabasePlatform());
        self::assertSame($nameGenerator, $migration->getNameGenerator());
        self::assertSame($logger, $migration->getLogger());
    }

    public function testAnotherValidExtension(): void
    {
        $migration = new MigrationWithTestExtension();
        $extension = new AnotherTestExtension();
        $platform = new MySQLPlatform();
        $nameGenerator = new DbIdentifierNameGenerator();
        $logger = $this->createMock(LoggerInterface::class);

        $manager = new MigrationExtensionManager();
        $manager->addExtension('test', $extension);
        $manager->applyExtensions($migration);

        self::assertSame($extension, $migration->getTestExtension());
        self::assertNull($extension->getDatabasePlatform());
        self::assertNull($extension->getNameGenerator());
        self::assertNull($extension->getLogger());

        $manager->setDatabasePlatform($platform);
        self::assertSame($platform, $extension->getDatabasePlatform());

        $manager->setNameGenerator($nameGenerator);
        self::assertSame($nameGenerator, $extension->getNameGenerator());

        $manager->setLogger($logger);
        self::assertSame($logger, $extension->getLogger());

        self::assertNull($migration->getDatabasePlatform());
        self::assertNull($migration->getNameGenerator());
        self::assertNull($migration->getLogger());
        $manager->applyExtensions($migration);
        self::assertSame($platform, $migration->getDatabasePlatform());
        self::assertSame($nameGenerator, $migration->getNameGenerator());
        self::assertSame($logger, $migration->getLogger());
    }

    public function testValidExtensionWithDependencies(): void
    {
        $migration = new MigrationWithTestExtension();
        $extension = new TestExtension();
        $platform = new MySQLPlatform();
        $nameGenerator = new DbIdentifierNameGenerator();
        $logger = $this->createMock(LoggerInterface::class);

        $manager = new MigrationExtensionManager();
        $manager->setDatabasePlatform($platform);
        $manager->setNameGenerator($nameGenerator);
        $manager->setLogger($logger);
        $manager->addExtension('test', $extension);
        $manager->applyExtensions($migration);

        self::assertSame($extension, $migration->getTestExtension());
        self::assertSame($platform, $extension->getDatabasePlatform());
        self::assertSame($nameGenerator, $extension->getNameGenerator());
        self::assertSame($logger, $extension->getLogger());
        self::assertSame($platform, $migration->getDatabasePlatform());
        self::assertSame($nameGenerator, $migration->getNameGenerator());
        self::assertSame($logger, $migration->getLogger());
    }

    public function testAnotherValidExtensionWithDependencies(): void
    {
        $migration = new MigrationWithTestExtension();
        $extension = new AnotherTestExtension();
        $platform = new MySQLPlatform();
        $nameGenerator = new DbIdentifierNameGenerator();
        $logger = $this->createMock(LoggerInterface::class);

        $manager = new MigrationExtensionManager();
        $manager->setDatabasePlatform($platform);
        $manager->setNameGenerator($nameGenerator);
        $manager->setLogger($logger);
        $manager->addExtension('test', $extension);
        $manager->applyExtensions($migration);

        self::assertSame($extension, $migration->getTestExtension());
        self::assertSame($platform, $extension->getDatabasePlatform());
        self::assertSame($nameGenerator, $extension->getNameGenerator());
        self::assertSame($logger, $extension->getLogger());
        self::assertSame($platform, $migration->getDatabasePlatform());
        self::assertSame($nameGenerator, $migration->getNameGenerator());
        self::assertSame($logger, $migration->getLogger());
    }

    public function testExtensionDependedToOtherExtension(): void
    {
        $migration = new MigrationWithTestExtensionDepended();
        $otherExtension = new TestExtension();
        $extension = new TestExtensionDepended();

        $manager = new MigrationExtensionManager();
        $manager->addExtension('test', $extension);
        $manager->addExtension('other', $otherExtension);
        $manager->applyExtensions($migration);

        self::assertSame($extension, $migration->getTestExtensionDepended());
        self::assertSame($otherExtension, $migration->getTestExtensionDepended()->getTestExtension());
    }

    public function testExtensionWithNoAwareInterface(): void
    {
        $dir = 'Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'The extension aware interface for "%s\NoAwareInterfaceExtension" was not found. '
            . 'Make sure that "%s\NoAwareInterfaceExtensionAwareInterface" interface is declared.',
            $dir,
            $dir
        ));

        $manager = new MigrationExtensionManager();
        $manager->addExtension('test', new NoAwareInterfaceExtension());
    }

    public function testAnotherExtensionWithNoAwareInterface(): void
    {
        $dir = 'Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'The extension aware interface for neither "%s\AnotherNoAwareInterfaceExtension"'
            . ' not one of its parent classes was not found.',
            $dir
        ));

        $manager = new MigrationExtensionManager();
        $manager->addExtension('test', new AnotherNoAwareInterfaceExtension());
    }

    public function testExtensionWithInvalidAwareInterface(): void
    {
        $dir = 'Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'The method "%s\InvalidAwareInterfaceExtensionAwareInterface::setInvalidAwareInterfaceExtension"'
            . ' was not found.',
            $dir
        ));

        $manager = new MigrationExtensionManager();
        $manager->addExtension('test', new InvalidAwareInterfaceExtension());
    }
}
