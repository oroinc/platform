<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;

class FieldTypeProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
    }

    public function testGetSupportedFieldTypes()
    {
        $types = ['string', 'boolean', 'date', 'file'];

        $provider = new FieldTypeProvider($this->configManager, $types, []);

        $this->assertEquals($types, $provider->getSupportedFieldTypes());
    }

    public function testGetSupportedRelationTypes()
    {
        $relations = ['oneToMany', 'manyToOne', 'manyToMany'];

        $provider = new FieldTypeProvider($this->configManager, [], $relations);

        $this->assertEquals($relations, $provider->getSupportedRelationTypes());
    }

    public function testGetFieldProperties()
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
