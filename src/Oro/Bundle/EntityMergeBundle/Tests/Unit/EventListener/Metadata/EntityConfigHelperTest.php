<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\EventListener\Metadata;

use Oro\Bundle\EntityMergeBundle\EventListener\Metadata\EntityConfigHelper;

class EntityConfigHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityConfigHelper
     */
    protected $helper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $extendConfigProvider;

    protected function setUp()
    {
        $this->configManager = $this
            ->getMockBuilder('Oro\\Bundle\\EntityConfigBundle\\Config\\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extendConfigProvider = $this->createConfigProvider();

        $this->helper = new EntityConfigHelper($this->configManager, $this->extendConfigProvider);
    }

    public function testGetConfigForExtendField()
    {
        $scope = 'merge';
        $className = 'Namespace\\Entity';
        $extendFieldName = EntityConfigHelper::EXTEND_FIELD_PREFIX . 'test';
        $fieldName = 'test';

        $this->configManager->expects($this->at(0))
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($this->extendConfigProvider));

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue(true));

        $extendConfig = $this->createConfig();

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue($extendConfig));

        $extendConfig->expects($this->once())
            ->method('is')
            ->with('is_extend')
            ->will($this->returnValue(true));

        $mergeConfigProvider = $this->createConfigProvider();

        $this->configManager->expects($this->at(1))
            ->method('getProvider')
            ->with($scope)
            ->will($this->returnValue($mergeConfigProvider));

        $mergeConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue(true));

        $mergeConfig = $this->createConfig();

        $mergeConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue($mergeConfig));

        $this->assertEquals($mergeConfig, $this->helper->getConfig($scope, $className, $extendFieldName));
    }

    public function testGetConfigByFieldMetadataForNotExtendField()
    {
        $scope = 'merge';
        $className = 'Namespace\\Entity';
        $fieldName = 'test';
        $extendFieldName = EntityConfigHelper::EXTEND_FIELD_PREFIX . $fieldName;

        $this->configManager->expects($this->at(0))
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($this->extendConfigProvider));

        $fieldMetadata = $this->createFieldMetadata();
        $fieldMetadata->expects($this->once())
            ->method('getSourceClassName')
            ->will($this->returnValue($className));
        $fieldMetadata->expects($this->once())
            ->method('getSourceFieldName')
            ->will($this->returnValue($extendFieldName));

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue(true));

        $extendConfig = $this->createConfig();

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue($extendConfig));

        $extendConfig->expects($this->once())
            ->method('is')
            ->with('is_extend')
            ->will($this->returnValue(true));

        $mergeConfigProvider = $this->createConfigProvider();

        $this->configManager->expects($this->at(1))
            ->method('getProvider')
            ->with($scope)
            ->will($this->returnValue($mergeConfigProvider));

        $mergeConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue(true));

        $mergeConfig = $this->createConfig();

        $mergeConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue($mergeConfig));

        $this->assertEquals($mergeConfig, $this->helper->getConfigByFieldMetadata($scope, $fieldMetadata));
    }

    public function testPrepareFieldMetadataPropertyPathWithExtendField()
    {
        $className = 'Namespace\\Entity';
        $fieldName = 'test';
        $extendFieldName = EntityConfigHelper::EXTEND_FIELD_PREFIX . $fieldName;

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($this->extendConfigProvider));

        $fieldMetadata = $this->createFieldMetadata();
        $fieldMetadata->expects($this->once())
            ->method('getSourceClassName')
            ->will($this->returnValue($className));
        $fieldMetadata->expects($this->once())
            ->method('getSourceFieldName')
            ->will($this->returnValue($extendFieldName));

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue(true));

        $extendConfig = $this->createConfig();

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue($extendConfig));

        $extendConfig->expects($this->once())
            ->method('is')
            ->with('is_extend')
            ->will($this->returnValue(true));

        $fieldMetadata->expects($this->once())
            ->method('set')
            ->with('property_path', $fieldName);

        $this->helper->prepareFieldMetadataPropertyPath($fieldMetadata);
    }

    public function testPrepareFieldMetadataPropertyPathWithNotExtendField()
    {
        $className = 'Namespace\\Entity';
        $fieldName = 'test';

        $fieldMetadata = $this->createFieldMetadata();
        $fieldMetadata->expects($this->once())
            ->method('getSourceClassName')
            ->will($this->returnValue($className));
        $fieldMetadata->expects($this->once())
            ->method('getSourceFieldName')
            ->will($this->returnValue($fieldName));

        $this->extendConfigProvider->expects($this->never())->method($this->anything());

        $fieldMetadata->expects($this->never())->method('set');

        $this->helper->prepareFieldMetadataPropertyPath($fieldMetadata);
    }

    protected function createFieldMetadata()
    {
        return $this->getMockBuilder('Oro\\Bundle\\EntityMergeBundle\\Metadata\\FieldMetadata')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createConfig()
    {
        return $this->getMock('Oro\\Bundle\\EntityConfigBundle\\Config\\ConfigInterface');
    }

    protected function createConfigProvider()
    {
        return $this->getMock('Oro\\Bundle\\EntityConfigBundle\\Provider\\ConfigProviderInterface');
    }
}
