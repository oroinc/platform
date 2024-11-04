<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Exception\RuntimeException;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProvider;
use Oro\Bundle\DataGridBundle\Provider\RawConfigurationProviderInterface;
use Oro\Bundle\DataGridBundle\Provider\SystemAwareResolver;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigurationProviderTest extends TestCase
{
    private RawConfigurationProviderInterface|MockObject $rawConfigurationProvider;

    private SystemAwareResolver|MockObject $resolver;

    private ConfigurationProvider $configurationProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->rawConfigurationProvider = $this->createMock(RawConfigurationProviderInterface::class);
        $this->resolver = $this->createMock(SystemAwareResolver::class);
        $this->configurationProvider = new ConfigurationProvider(
            $this->rawConfigurationProvider,
            $this->resolver
        );
    }

    public function testGetConfiguration(): void
    {
        $gridName = 'test_grid';
        $rawConfig = ['extend_entity_name' => \stdClass::class];

        $configuration = DatagridConfiguration::createNamed(
            $gridName,
            $rawConfig,
            PropertyAccess::createPropertyAccessorWithDotSyntax()
        );

        $this->rawConfigurationProvider
            ->expects(self::once())
            ->method('getRawConfiguration')
            ->with($gridName)
            ->willReturn($rawConfig);

        $this->resolver
            ->expects(self::once())
            ->method('resolve')
            ->with($gridName, $rawConfig)
            ->willReturn($rawConfig);

        self::assertEquals($configuration, $this->configurationProvider->getConfiguration($gridName));
    }

    public function testGetConfigurationThrowException(): void
    {
        $gridName = 'test_grid';

        $this->rawConfigurationProvider
            ->expects(self::once())
            ->method('getRawConfiguration')
            ->with($gridName)
            ->willReturn(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'A configuration for "%s" datagrid was not found.',
            $gridName
        ));

        $this->configurationProvider->getConfiguration($gridName);
    }

    /** @dataProvider configDataProvider */
    public function testIsApplicable(string $gridName, ?array $rawConfiguration, bool $expected): void
    {
        $this->rawConfigurationProvider
            ->expects(self::once())
            ->method('getRawConfiguration')
            ->with($gridName)
            ->willReturn($rawConfiguration);

        self::assertEquals($expected, $this->configurationProvider->isApplicable($gridName));
    }

    /** @dataProvider configDataProvider */
    public function testIsValidConfiguration(string $gridName, ?array $rawConfiguration, bool $expected): void
    {
        $this->rawConfigurationProvider
            ->expects(self::any())
            ->method('getRawConfiguration')
            ->with($gridName)
            ->willReturn($rawConfiguration);

        $this->resolver
            ->expects(self::any())
            ->method('resolve')
            ->with($gridName, $rawConfiguration)
            ->willReturn($rawConfiguration);

        self::assertEquals($expected, $this->configurationProvider->isValidConfiguration($gridName));
    }

    public function configDataProvider(): array
    {
        return [
            'ok' => [
                'gridName' => 'test_grid',
                'rawConfiguration' => [],
                'expected' => true,
            ],
            'not-ok' => [
                'gridName' => 'test_grid',
                'rawConfiguration' => null,
                'expected' => false,
            ]
        ];
    }
}
