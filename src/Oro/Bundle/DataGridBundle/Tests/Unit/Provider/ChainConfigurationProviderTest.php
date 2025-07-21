<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Exception\RuntimeException;
use Oro\Bundle\DataGridBundle\Provider\ChainConfigurationProvider;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use PHPUnit\Framework\TestCase;

class ChainConfigurationProviderTest extends TestCase
{
    private ConfigurationProviderInterface $configurationProvider;
    private ChainConfigurationProvider $chainConfigurationProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configurationProvider = $this->createMock(ConfigurationProviderInterface::class);
        $this->chainConfigurationProvider = new ChainConfigurationProvider([$this->configurationProvider]);
    }

    public function testIsApplicable(): void
    {
        self::assertTrue($this->chainConfigurationProvider->isApplicable('test_grid'));
    }

    public function testGetProviders(): void
    {
        self::assertEquals([$this->configurationProvider], $this->chainConfigurationProvider->getProviders());
    }

    public function testGetConfiguration(): void
    {
        $gridName = 'test_grid';
        $rawConfig = ['extend_entity_name' => \stdClass::class];
        $config = DatagridConfiguration::createNamed(
            $gridName,
            $rawConfig,
            PropertyAccess::createPropertyAccessorWithDotSyntax()
        );

        $this->configurationProvider->expects(self::once())
            ->method('isApplicable')
            ->with($gridName)
            ->willReturn(true);

        $this->configurationProvider->expects(self::once())
            ->method('getConfiguration')
            ->with($gridName)
            ->willReturn($config);

        self::assertEquals($config, $this->chainConfigurationProvider->getConfiguration($gridName));
    }

    public function testGetConfigurationThrowException(): void
    {
        $gridName = 'test_grid';
        $rawConfig = ['extend_entity_name' => \stdClass::class];
        $config = DatagridConfiguration::createNamed(
            $gridName,
            $rawConfig,
            PropertyAccess::createPropertyAccessorWithDotSyntax()
        );

        $this->configurationProvider->expects(self::once())
            ->method('isApplicable')
            ->with($gridName)
            ->willReturn(false);

        $this->configurationProvider->expects(self::never())
            ->method('getConfiguration')
            ->with($gridName)
            ->willReturn($config);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('A configuration for "%s" datagrid was not found.', $gridName));

        $this->chainConfigurationProvider->getConfiguration($gridName);
    }

    /** @dataProvider configDataProvider */
    public function testIsValidConfiguration(string $gridName, bool $isApplicable, bool $isValid, bool $expected): void
    {
        $this->configurationProvider->expects(self::any())
            ->method('isApplicable')
            ->with($gridName)
            ->willReturn($isApplicable);

        $this->configurationProvider->expects(self::any())
            ->method('isValidConfiguration')
            ->with($gridName)
            ->willReturn($isValid);


        self::assertEquals($expected, $this->chainConfigurationProvider->isValidConfiguration($gridName));
    }

    public function configDataProvider(): array
    {
        return [
            'configuration applicable and valid' => [
                'gridName' => 'test_grid',
                'isApplicable' => true,
                'isValid' => true,
                'expected' => true,
            ],
            'configuration not applicable and valid' => [
                'gridName' => 'test_grid',
                'isApplicable' => false,
                'isValid' => true,
                'expected' => false,
            ],
            'configuration applicable and not valid' => [
                'gridName' => 'test_grid',
                'isApplicable' => true,
                'isValid' => false,
                'expected' => false,
            ],
            'configuration not applicable and not valid' => [
                'gridName' => 'test_grid',
                'isApplicable' => false,
                'isValid' => false,
                'expected' => false,
            ]
        ];
    }
}
