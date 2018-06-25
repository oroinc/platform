<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationRegistry;
use PHPUnit\Framework\TestCase;

class ImportExportConfigurationRegistryTest extends TestCase
{
    public function testConfigurationAddedAndReturned()
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

        static::assertSame(
            [
                $configurations[0],
                $configurations[1],
            ],
            $registry->getConfigurations($aliases[0])
        );

        static::assertSame([$configurations[2]], $registry->getConfigurations($aliases[1]));
    }

    public function testGetConfigurationForUndefinedAlias()
    {
        $registry = new ImportExportConfigurationRegistry();

        static::assertCount(0, $registry->getConfigurations('1'));
    }

    /**
     * @param ImportExportConfigurationInterface $configuration
     *
     * @return ImportExportConfigurationProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createProvider(ImportExportConfigurationInterface $configuration)
    {
        $provider = $this->createMock(ImportExportConfigurationProviderInterface::class);
        $provider->expects(static::once())
            ->method('get')
            ->willReturn($configuration);

        return $provider;
    }
}
