<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Oro\Bundle\MigrationBundle\Migration\MigrationExtensionManager;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension\InvalidAwareInterfaceExtension;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension\TestExtension;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension\AnotherTestExtension;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\MigrationWithTestExtension;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension\NoAwareInterfaceExtension;
use Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension\AnotherNoAwareInterfaceExtension;

class MigrationExtensionManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testValidExtension()
    {
        $migration = new MigrationWithTestExtension();
        $extension = new TestExtension();
        $platform = new MySqlPlatform();

        $manager = new MigrationExtensionManager();
        $manager->addExtension('test', $extension);
        $manager->applyExtensions($migration);

        $this->assertSame($extension, $migration->getTestExtension());
        $this->assertNull($extension->getDatabasePlatform());

        $manager->setDatabasePlatform($platform);
        $this->assertSame($platform, $extension->getDatabasePlatform());
    }

    public function testAnotherValidExtension()
    {
        $migration = new MigrationWithTestExtension();
        $extension = new AnotherTestExtension();
        $platform = new MySqlPlatform();

        $manager = new MigrationExtensionManager();
        $manager->addExtension('test', $extension);
        $manager->applyExtensions($migration);

        $this->assertSame($extension, $migration->getTestExtension());
        $this->assertNull($extension->getDatabasePlatform());

        $manager->setDatabasePlatform($platform);
        $this->assertSame($platform, $extension->getDatabasePlatform());
    }

    public function testValidExtensionWithDatabasePlatform()
    {
        $migration = new MigrationWithTestExtension();
        $extension = new TestExtension();
        $platform = new MySqlPlatform();

        $manager = new MigrationExtensionManager();
        $manager->setDatabasePlatform($platform);
        $manager->addExtension('test', $extension);
        $manager->applyExtensions($migration);

        $this->assertSame($extension, $migration->getTestExtension());
        $this->assertSame($platform, $extension->getDatabasePlatform());
    }

    public function testAnotherValidExtensionWithDatabasePlatform()
    {
        $migration = new MigrationWithTestExtension();
        $extension = new AnotherTestExtension();
        $platform = new MySqlPlatform();

        $manager = new MigrationExtensionManager();
        $manager->setDatabasePlatform($platform);
        $manager->addExtension('test', $extension);
        $manager->applyExtensions($migration);

        $this->assertSame($extension, $migration->getTestExtension());
        $this->assertSame($platform, $extension->getDatabasePlatform());
    }

    public function testExtensionWithNoAwareInterface()
    {
        $dir = 'Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension';
        $this->setExpectedException(
            '\RuntimeException',
            sprintf(
                'The extension aware interface for "%s\NoAwareInterfaceExtension" was not found. '
                . 'Make sure that "%s\NoAwareInterfaceExtensionAwareInterface" interface is declared.',
                $dir,
                $dir
            )
        );

        $manager = new MigrationExtensionManager();
        $manager->addExtension('test', new NoAwareInterfaceExtension());
    }

    public function testAnotherExtensionWithNoAwareInterface()
    {
        $dir = 'Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension';
        $this->setExpectedException(
            '\RuntimeException',
            sprintf(
                'The extension aware interface for neither "%s\AnotherNoAwareInterfaceExtension"'
                . ' not one of its parent classes was not found.',
                $dir
            )
        );

        $manager = new MigrationExtensionManager();
        $manager->addExtension('test', new AnotherNoAwareInterfaceExtension());
    }

    public function testExtensionWithInvalidAwareInterface()
    {
        $dir = 'Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension';
        $this->setExpectedException(
            '\RuntimeException',
            sprintf(
                'The method "%s\InvalidAwareInterfaceExtensionAwareInterface::setInvalidAwareInterfaceExtension"'
                . ' was not found.',
                $dir
            )
        );

        $manager = new MigrationExtensionManager();
        $manager->addExtension('test', new InvalidAwareInterfaceExtension());
    }
}
