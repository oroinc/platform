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
    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    /**
     * @var SystemConfigFallbackProvider
     */
    protected $systemConfigFallbackProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $configProvider;

    /** @var ConfigInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $configInterface;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->systemConfigFallbackProvider = new SystemConfigFallbackProvider($this->configManager);
        $this->configProvider = $this->getMockBuilder(ConfigProvider::class)->disableOriginalConstructor()->getMock();
        $this->systemConfigFallbackProvider->setConfigProvider($this->configProvider);
        $this->configInterface = $this->createMock(ConfigInterface::class);
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

    protected function setUpFallbackConfig($entityConfig)
    {
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->configInterface);
        $this->configInterface->expects($this->once())
            ->method('getValues')
            ->willReturn($entityConfig);
    }

    protected function getEntityConfiguration()
    {
        return [
            EntityFieldFallbackValue::FALLBACK_LIST => [
                SystemConfigFallbackProvider::FALLBACK_ID => ['configName' => 'test_config_name'],
            ],
            EntityFieldFallbackValue::FALLBACK_TYPE => 'boolean',
        ];
    }
}
