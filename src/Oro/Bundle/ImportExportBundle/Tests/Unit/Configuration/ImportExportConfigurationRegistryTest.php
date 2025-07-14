<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationRegistry;
use PHPUnit\Framework\TestCase;

class ImportExportConfigurationRegistryTest extends TestCase
{
    public function testConfigurationAddedAndReturned(): void
    {
        $configurations = [
            $this->createMock(ImportExportConfigurationInterface::class),
            $this->createMock(ImportExportConfigurationInterface::class),
            $this->createMock(ImportExportConfigurationInterface::class),
        ];

        $providers = [
            $this->createProvider($configurations[0]),
            $this->createProvider($configurations[1]),
            $this->createProvider($configurations[2]),
        ];

        $aliases = ['01', '2'];

        $registry = new ImportExportConfigurationRegistry();

        $registry->addConfiguration($providers[0], $aliases[0]);
        $registry->addConfiguration($providers[1], $aliases[0]);
        $registry->addConfiguration($providers[2], $aliases[1]);

        self::assertSame(
            [
                $configurations[0],
                $configurations[1],
            ],
            $registry->getConfigurations($aliases[0])
        );

        self::assertSame([$configurations[2]], $registry->getConfigurations($aliases[1]));
    }

    public function testGetConfigurationForUndefinedAlias(): void
    {
        $registry = new ImportExportConfigurationRegistry();

        self::assertCount(0, $registry->getConfigurations('1'));
    }

    private function createProvider(
        ImportExportConfigurationInterface $configuration
    ): ImportExportConfigurationProviderInterface {
        $provider = $this->createMock(ImportExportConfigurationProviderInterface::class);
        $provider->expects(self::once())
            ->method('get')
            ->willReturn($configuration);

        return $provider;
    }
}
