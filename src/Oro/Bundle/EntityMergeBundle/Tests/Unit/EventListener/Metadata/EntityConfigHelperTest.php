<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\EventListener\Metadata;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityMergeBundle\EventListener\Metadata\EntityConfigHelper;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

class EntityConfigHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $extendConfigProvider;

    /** @var EntityConfigHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);

        $this->helper = new EntityConfigHelper($this->configManager);
    }

    public function testGetConfigForExtendField()
    {
        $scope = 'merge';
        $className = 'Namespace\Entity';
        $fieldName = 'test';

        $mergeConfigProvider = $this->createMock(ConfigProvider::class);

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with($scope)
            ->willReturn($mergeConfigProvider);

        $mergeConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->willReturn(true);

        $mergeConfig = $this->createMock(ConfigInterface::class);

        $mergeConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->willReturn($mergeConfig);

        $this->assertSame($mergeConfig, $this->helper->getConfig($scope, $className, $fieldName));
    }

    public function testGetConfigByFieldMetadataForNotExtendField()
    {
        $scope = 'merge';
        $className = 'Namespace\Entity';
        $fieldName = 'test';

        $fieldMetadata = $this->createMock(FieldMetadata::class);
        $fieldMetadata->expects($this->once())
            ->method('getSourceClassName')
            ->willReturn($className);
        $fieldMetadata->expects($this->once())
            ->method('getSourceFieldName')
            ->willReturn($fieldName);

        $mergeConfigProvider = $this->createMock(ConfigProvider::class);

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with($scope)
            ->willReturn($mergeConfigProvider);

        $mergeConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->willReturn(true);

        $mergeConfig = $this->createMock(ConfigInterface::class);

        $mergeConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->willReturn($mergeConfig);

        $this->assertSame($mergeConfig, $this->helper->getConfigByFieldMetadata($scope, $fieldMetadata));
    }

    public function testPrepareFieldMetadataPropertyPathWithExtendField()
    {
        $className = 'Namespace\Entity';
        $fieldName = 'test';

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($this->extendConfigProvider);

        $fieldMetadata = $this->createMock(FieldMetadata::class);
        $fieldMetadata->expects($this->once())
            ->method('getSourceClassName')
            ->willReturn($className);
        $fieldMetadata->expects($this->once())
            ->method('getSourceFieldName')
            ->willReturn($fieldName);

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->willReturn(true);

        $extendConfig = $this->createMock(ConfigInterface::class);

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->willReturn($extendConfig);

        $extendConfig->expects($this->any())
            ->method('is')
            ->with('is_extend')
            ->willReturn(true);

        $fieldMetadata->expects($this->exactly(2))
            ->method('set')
            ->withConsecutive(
                ['property_path', $fieldName],
                ['display', true]
            );

        $this->helper->prepareFieldMetadataPropertyPath($fieldMetadata);
    }

    public function testPrepareFieldMetadataPropertyPathWithNotExtendField()
    {
        $className = 'Namespace\\Entity';
        $fieldName = 'test';

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($this->extendConfigProvider);

        $fieldMetadata = $this->createMock(FieldMetadata::class);
        $fieldMetadata->expects($this->once())
            ->method('getSourceClassName')
            ->willReturn($className);
        $fieldMetadata->expects($this->once())
            ->method('getSourceFieldName')
            ->willReturn($fieldName);

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->willReturn(false);

        $fieldMetadata->expects($this->never())
            ->method('set');

        $this->helper->prepareFieldMetadataPropertyPath($fieldMetadata);
    }
}
