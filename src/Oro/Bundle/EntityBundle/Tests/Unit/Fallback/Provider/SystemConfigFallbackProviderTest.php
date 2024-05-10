<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Fallback\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Exception\Fallback\FallbackFieldConfigurationMissingException;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SystemConfigFallbackProviderTest extends TestCase
{
    private ConfigManager|MockObject $configManager;

    private ConfigProvider|MockObject $configProvider;

    private SystemConfigFallbackProvider $systemConfigFallbackProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->systemConfigFallbackProvider = new SystemConfigFallbackProvider($this->configManager);
        $this->systemConfigFallbackProvider->setConfigProvider($this->configProvider);
    }

    public function testIsFallbackSupportedReturnsTrue(): void
    {
        self::assertTrue($this->systemConfigFallbackProvider->isFallbackSupported(new \stdClass(), 'test'));
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

        $this->systemConfigFallbackProvider->getFallbackHolderEntity(new \stdClass(), 'test');
        // check the local entity config cache
        $this->systemConfigFallbackProvider->getFallbackHolderEntity(new \stdClass(), 'test');
    }

    public function testGetFallbackHolderEntityReturnsCorrectValue(): void
    {
        $this->setUpFallbackConfig($this->getEntityConfiguration());
        $expectedValue = 'testValue';
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('test_config_name')
            ->willReturn($expectedValue);
        $result = $this->systemConfigFallbackProvider->getFallbackHolderEntity(new \stdClass(), 'test');
        self::assertEquals($expectedValue, $result);
    }

    public function testGetFallbackEntityClass(): void
    {
        self::assertNull($this->systemConfigFallbackProvider->getFallbackEntityClass());
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
                    SystemConfigFallbackProvider::FALLBACK_ID,
                    \stdClass::class,
                    'test'
                ),
                'entityConfig' => [EntityFieldFallbackValue::FALLBACK_LIST => []]
            ],
            'no configName' => [
                'expectedMessage' => sprintf(
                    "You must define the '%s' fallback option for entity '%s' field '%s', fallback id '%s'",
                    SystemConfigFallbackProvider::CONFIG_NAME_KEY,
                    \stdClass::class,
                    'test',
                    SystemConfigFallbackProvider::FALLBACK_ID
                ),
                'entityConfig' => [
                    EntityFieldFallbackValue::FALLBACK_LIST => [
                        SystemConfigFallbackProvider::FALLBACK_ID => []
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
                SystemConfigFallbackProvider::FALLBACK_ID => [
                    SystemConfigFallbackProvider::CONFIG_NAME_KEY => 'test_config_name'
                ],
            ],
            EntityFieldFallbackValue::FALLBACK_TYPE => 'boolean',
        ];
    }
}
