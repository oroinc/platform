<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Fallback\Provider;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Exception\Fallback\FallbackFieldConfigurationMissingException;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ThemeBundle\Fallback\Provider\ThemeConfigurationFallbackProvider;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ThemeConfigurationFallbackProviderTest extends TestCase
{
    private ThemeConfigurationProvider|MockObject $themeConfigurationProvider;
    private ConfigProvider|MockObject $configProvider;

    private ThemeConfigurationFallbackProvider $themeConfigurationFallbackProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->themeConfigurationProvider = $this->createMock(ThemeConfigurationProvider::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->themeConfigurationFallbackProvider = new ThemeConfigurationFallbackProvider(
            $this->themeConfigurationProvider
        );
        $this->themeConfigurationFallbackProvider->setConfigProvider($this->configProvider);
    }

    public function testIsFallbackSupportedReturnsTrue(): void
    {
        self::assertTrue($this->themeConfigurationFallbackProvider->isFallbackSupported(new \stdClass(), 'test'));
    }

    /**
     * @dataProvider getFallbackHolderEntityDataProvider
     */
    public function testGetFallbackHolderEntityThrowsExceptionIfNoConfigFound(
        string $expectedMessage,
        array $entityConfig
    ): void {
        self::expectException(FallbackFieldConfigurationMissingException::class);
        self::expectExceptionMessage($expectedMessage);

        $this->setUpFallbackConfig($entityConfig);

        $this->themeConfigurationFallbackProvider->getFallbackHolderEntity(new \stdClass(), 'test');
        // check the local entity config cache
        $this->themeConfigurationFallbackProvider->getFallbackHolderEntity(new \stdClass(), 'test');
    }

    public function testGetFallbackHolderEntityReturnsCorrectValue(): void
    {
        $this->setUpFallbackConfig($this->getEntityConfiguration());
        $expectedValue = 'testValue';
        $this->themeConfigurationProvider->expects(self::once())
            ->method('getThemeConfigurationOption')
            ->with('test_themeConfiguration_name')
            ->willReturn($expectedValue);

        $result = $this->themeConfigurationFallbackProvider->getFallbackHolderEntity(new \stdClass(), 'test');

        self::assertEquals($expectedValue, $result);
    }

    public function testGetFallbackEntityClass(): void
    {
        self::assertNull($this->themeConfigurationFallbackProvider->getFallbackEntityClass());
    }

    public function getFallbackHolderEntityDataProvider(): array
    {
        return [
            'no fallback list' => [
                'expectedMessage' => sprintf(
                    "You must define the fallback configuration '%s' for the class '%s' field '%s'",
                    EntityFieldFallbackValue::FALLBACK_LIST,
                    \stdClass::class,
                    'test'
                ),
                'entityConfig' => []
            ],
            'no fallback id' => [
                'expectedMessage' => sprintf(
                    "You must define the fallback id configuration '%s' for the class '%s' field '%s'",
                    ThemeConfigurationFallbackProvider::FALLBACK_ID,
                    \stdClass::class,
                    'test'
                ),
                'entityConfig' => [EntityFieldFallbackValue::FALLBACK_LIST => []]
            ],
            'no configName' => [
                'expectedMessage' => sprintf(
                    "You must define the '%s' fallback option for entity '%s' field '%s', fallback id '%s'",
                    ThemeConfigurationFallbackProvider::CONFIG_NAME_KEY,
                    \stdClass::class,
                    'test',
                    ThemeConfigurationFallbackProvider::FALLBACK_ID
                ),
                'entityConfig' => [
                    EntityFieldFallbackValue::FALLBACK_LIST => [
                        ThemeConfigurationFallbackProvider::FALLBACK_ID => []
                    ]
                ]
            ]
        ];
    }

    private function setUpFallbackConfig($entityConfig): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);
        $config->expects(self::once())
            ->method('getValues')
            ->willReturn($entityConfig);
    }

    private function getEntityConfiguration(): array
    {
        return [
            EntityFieldFallbackValue::FALLBACK_LIST => [
                ThemeConfigurationFallbackProvider::FALLBACK_ID => [
                    ThemeConfigurationFallbackProvider::CONFIG_NAME_KEY => 'test_themeConfiguration_name'
                ],
            ],
            EntityFieldFallbackValue::FALLBACK_TYPE => 'boolean',
        ];
    }
}
