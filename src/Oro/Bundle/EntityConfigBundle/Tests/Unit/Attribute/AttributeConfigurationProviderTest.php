<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute;

use Oro\Bundle\EntityConfigBundle\Attribute\AttributeConfigurationProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Layout\DataProvider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttributeConfigurationProviderTest extends TestCase
{
    private const CLASS_NAME = \stdClass::class;
    private const FIELD_NAME = 'test_field';

    private ConfigInterface&MockObject $entityConfig;
    private ConfigInterface&MockObject $extendConfig;
    private ConfigInterface&MockObject $attributeConfig;
    private AttributeConfigurationProvider $provider;
    private FieldConfigModel $attribute;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityConfig = $this->createMock(ConfigInterface::class);
        $this->extendConfig = $this->createMock(ConfigInterface::class);
        $this->attributeConfig = $this->createMock(ConfigInterface::class);

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->once())
            ->method('getProvider')
            ->willReturnMap([
                ['entity', $this->getConfigProvider($this->entityConfig)],
                ['extend', $this->getConfigProvider($this->extendConfig)],
                ['attribute', $this->getConfigProvider($this->attributeConfig)],
            ]);

        $this->provider = new AttributeConfigurationProvider($configManager);

        $this->attribute = new FieldConfigModel(self::FIELD_NAME);
        $this->attribute->setEntity(new EntityConfigModel(self::CLASS_NAME));
    }

    public function testGetAttributeLabel(): void
    {
        $this->entityConfig->expects($this->once())
            ->method('get')
            ->with('label', false, self::FIELD_NAME)
            ->willReturn('test label');

        $this->assertEquals('test label', $this->provider->getAttributeLabel($this->attribute));
    }

    public function testIsAttributeActive(): void
    {
        $this->extendConfig->expects($this->once())
            ->method('in')
            ->with('state', [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE])
            ->willReturn(true);

        $this->assertTrue($this->provider->isAttributeActive($this->attribute));
    }

    public function testIsAttributeSearchable(): void
    {
        $this->attributeConfig->expects($this->once())
            ->method('is')
            ->with('searchable')
            ->willReturn(true);

        $this->assertTrue($this->provider->isAttributeSearchable($this->attribute));
    }

    public function testIsAttributeFilterable(): void
    {
        $this->attributeConfig->expects($this->once())
            ->method('is')
            ->with('filterable')
            ->willReturn(true);

        $this->assertTrue($this->provider->isAttributeFilterable($this->attribute));
    }

    public function testIsAttributeFilterByExactValue(): void
    {
        $this->attributeConfig->expects($this->once())
            ->method('is')
            ->with('filter_by', 'exact_value')
            ->willReturn(true);

        $this->assertTrue($this->provider->isAttributeFilterByExactValue($this->attribute));
    }

    public function testIsAttributeSortable(): void
    {
        $this->attributeConfig->expects($this->once())
            ->method('is')
            ->with('sortable')
            ->willReturn(true);

        $this->assertTrue($this->provider->isAttributeSortable($this->attribute));
    }

    private function getConfigProvider(ConfigInterface $config): ConfigProvider
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->expects($this->any())
            ->method('getConfig')
            ->with(self::CLASS_NAME, self::FIELD_NAME)
            ->willReturn($config);

        return $configProvider;
    }
}
