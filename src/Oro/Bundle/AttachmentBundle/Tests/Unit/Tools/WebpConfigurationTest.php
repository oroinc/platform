<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Tools;

use Oro\Bundle\AttachmentBundle\DependencyInjection\Configuration;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class WebpConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getIsEnabledIfSupportedDataProvider
     */
    public function testIsEnabledIfSupported(string $strategy, bool $expectedResult): void
    {
        $webpConfiguration = new WebpConfiguration($this->createMock(ConfigManager::class), $strategy);

        self::assertEquals(
            $expectedResult,
            $webpConfiguration->isEnabledIfSupported()
        );
    }

    public function getIsEnabledIfSupportedDataProvider(): array
    {
        return [
            [
                'strategy' => 'unknown',
                'expectedResult' => false,
            ],
            [
                'strategy' => WebpConfiguration::DISABLED,
                'expectedResult' => false,
            ],
            [
                'strategy' => WebpConfiguration::ENABLED_FOR_ALL,
                'expectedResult' => false,
            ],
            [
                'strategy' => WebpConfiguration::ENABLED_IF_SUPPORTED,
                'expectedResult' => true,
            ],
        ];
    }

    /**
     * @dataProvider getIsEnabledForAllDataProvider
     */
    public function testIsEnabledForAll(string $strategy, bool $expectedResult): void
    {
        $webpConfiguration = new WebpConfiguration($this->createMock(ConfigManager::class), $strategy);

        self::assertEquals(
            $expectedResult,
            $webpConfiguration->isEnabledForAll()
        );
    }

    public function getIsEnabledForAllDataProvider(): array
    {
        return [
            [
                'strategy' => 'unknown',
                'expectedResult' => false,
            ],
            [
                'strategy' => WebpConfiguration::DISABLED,
                'expectedResult' => false,
            ],
            [
                'strategy' => WebpConfiguration::ENABLED_FOR_ALL,
                'expectedResult' => true,
            ],
            [
                'strategy' => WebpConfiguration::ENABLED_IF_SUPPORTED,
                'expectedResult' => false,
            ],
        ];
    }

    /**
     * @dataProvider getIsEnabledForAllDataProvider
     */
    public function testIsDisabled(string $strategy, bool $expectedResult): void
    {
        $webpConfiguration = new WebpConfiguration($this->createMock(ConfigManager::class), $strategy);

        self::assertEquals(
            $expectedResult,
            $webpConfiguration->isEnabledForAll()
        );
    }

    public function getIsDisabledDataProvider(): array
    {
        return [
            [
                'strategy' => 'unknown',
                'expectedResult' => false,
            ],
            [
                'strategy' => WebpConfiguration::DISABLED,
                'expectedResult' => true,
            ],
            [
                'strategy' => WebpConfiguration::ENABLED_FOR_ALL,
                'expectedResult' => false,
            ],
            [
                'strategy' => WebpConfiguration::ENABLED_IF_SUPPORTED,
                'expectedResult' => false,
            ],
        ];
    }

    /**
     * @dataProvider getWebpQualityDataProvider
     */
    public function testGetWebpQuality(string|int|float|null $quality, int $expected): void
    {
        $configManager = $this->createMock(ConfigManager::class);
        $configManager
            ->expects(self::once())
            ->method('get')
            ->with('oro_attachment.webp_quality')
            ->willReturn($quality);

        $webpConfiguration = new WebpConfiguration($configManager, '');

        self::assertSame($expected, $webpConfiguration->getWebpQuality());
    }

    public function getWebpQualityDataProvider(): array
    {
        return [
            ['quality' => null, 'expected' => Configuration::WEBP_QUALITY],
            ['quality' => 'invalid', 'expected' => Configuration::WEBP_QUALITY],
            ['quality' => 0, 'expected' => Configuration::WEBP_QUALITY],
            ['quality' => -100, 'expected' => Configuration::WEBP_QUALITY],
            ['quality' => 1000, 'expected' => Configuration::WEBP_QUALITY],
            ['quality' => '75', 'expected' => 75],
            ['quality' => 75.5, 'expected' => 75],
            ['quality' => 75.9, 'expected' => 75],
            ['quality' => 50, 'expected' => 50],
            ['quality' => 100, 'expected' => 100],
        ];
    }
}
