<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Fallback\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Exception\Fallback\FallbackFieldConfigurationMissingException;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class SystemConfigFallbackProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var SystemConfigFallbackProvider */
    private $systemConfigFallbackProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->systemConfigFallbackProvider = new SystemConfigFallbackProvider($this->configManager);
        $this->systemConfigFallbackProvider->setConfigProvider($this->configProvider);
    }

    public function testIsFallbackSupportedReturnsTrue()
    {
        $this->assertTrue($this->systemConfigFallbackProvider->isFallbackSupported(new \stdClass(), 'test'));
    }

    public function testGetFallbackHolderEntityThrowsExceptionIfNoConfigFound()
    {
        $this->expectException(FallbackFieldConfigurationMissingException::class);
        $entityConfig = $this->getEntityConfiguration();
        $entityConfig[EntityFieldFallbackValue::FALLBACK_LIST][SystemConfigFallbackProvider::FALLBACK_ID] = [];
        $this->setUpFallbackConfig($entityConfig);

        $this->systemConfigFallbackProvider->getFallbackHolderEntity(new \stdClass(), 'test');

        // check the local entity config cache
        $this->systemConfigFallbackProvider->getFallbackHolderEntity(new \stdClass(), 'test');
    }

    public function testGetFallbackHolderEntityReturnsCorrectValue()
    {
        $this->setUpFallbackConfig($this->getEntityConfiguration());
        $expectedValue = 'testValue';
        $this->configManager->expects($this->once())
            ->method('get')
            ->willReturn($expectedValue);
        $result = $this->systemConfigFallbackProvider->getFallbackHolderEntity(new \stdClass(), 'test');
        $this->assertEquals($expectedValue, $result);
    }

    public function testGetFallbackEntityClass()
    {
        $this->assertNull($this->systemConfigFallbackProvider->getFallbackEntityClass());
    }

    private function setUpFallbackConfig($entityConfig): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);
        $config->expects($this->once())
            ->method('getValues')
            ->willReturn($entityConfig);
    }

    private function getEntityConfiguration(): array
    {
        return [
            EntityFieldFallbackValue::FALLBACK_LIST => [
                SystemConfigFallbackProvider::FALLBACK_ID => ['configName' => 'test_config_name'],
            ],
            EntityFieldFallbackValue::FALLBACK_TYPE => 'boolean',
        ];
    }
}
