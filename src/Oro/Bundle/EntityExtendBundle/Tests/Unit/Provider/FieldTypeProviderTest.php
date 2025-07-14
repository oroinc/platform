<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FieldTypeProviderTest extends TestCase
{
    private ConfigManager&MockObject $configManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
    }

    public function testGetSupportedFieldTypes(): void
    {
        $types = ['string', 'boolean', 'date', 'file'];

        $provider = new FieldTypeProvider($this->configManager, $types, []);

        $this->assertEquals($types, $provider->getSupportedFieldTypes());
    }

    public function testGetSupportedRelationTypes(): void
    {
        $relations = ['oneToMany', 'manyToOne', 'manyToMany'];

        $provider = new FieldTypeProvider($this->configManager, [], $relations);

        $this->assertEquals($relations, $provider->getSupportedRelationTypes());
    }

    public function testGetFieldProperties(): void
    {
        $configType = PropertyConfigContainer::TYPE_FIELD;
        $fieldType = 'string';

        $scope = 'testScope';
        $code = 'test_code';

        $providerConfig = [
            $code => [
                'options' => [],
                'form' => [],
                'import_export' => ['import_template' => ['use_in_template' => true]]
            ]
        ];

        $propertyConfig = $this->createMock(PropertyConfigContainer::class);
        $propertyConfig->expects(self::once())
            ->method('hasForm')
            ->with($configType, $fieldType)
            ->willReturn($propertyConfig);
        $propertyConfig->expects(self::once())
            ->method('getItems')
            ->with($configType)
            ->willReturn($providerConfig);

        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->expects(self::once())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfig);
        $configProvider->expects(self::once())
            ->method('getScope')
            ->willReturn($scope);

        $this->configManager->expects(self::once())
            ->method('getProviders')
            ->willReturn([$configProvider]);

        $provider = new FieldTypeProvider($this->configManager, [], []);

        $this->assertEquals([$scope => $providerConfig], $provider->getFieldProperties($fieldType, $configType));
    }
}
