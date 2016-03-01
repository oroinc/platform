<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Twig\ConfigExtension;

class ConfigExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigExtension
     */
    protected $twigExtension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    protected function setUp()
    {
        $this->configManager   = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $router                = $this->getMock('Symfony\Component\Routing\RouterInterface');
        $entityClassNameHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->twigExtension = new ConfigExtension($this->configManager, $router, $entityClassNameHelper);
    }

    protected function tearDown()
    {
        unset($this->configManager);
        unset($this->twigExtension);
    }

    public function testGetFunctions()
    {
        $functions = $this->twigExtension->getFunctions();
        $this->assertCount(7, $functions);

        /** @var \Twig_SimpleFunction $function */
        $function = $functions[0];
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertEquals('oro_entity_config', $function->getName());
        $this->assertEquals(array($this->twigExtension, 'getClassConfig'), $function->getCallable());

        /** @var \Twig_SimpleFunction $function */
        $function = $functions[1];
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertEquals('oro_entity_config_value', $function->getName());
        $this->assertEquals(array($this->twigExtension, 'getClassConfigValue'), $function->getCallable());

        /** @var \Twig_SimpleFunction $function */
        $function = $functions[2];
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertEquals('oro_field_config', $function->getName());
        $this->assertEquals(array($this->twigExtension, 'getFieldConfig'), $function->getCallable());

        /** @var \Twig_SimpleFunction $function */
        $function = $functions[3];
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertEquals('oro_field_config_value', $function->getName());
        $this->assertEquals(array($this->twigExtension, 'getFieldConfigValue'), $function->getCallable());

        /** @var \Twig_SimpleFunction $function */
        $function = $functions[4];
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertEquals('oro_entity_route', $function->getName());
        $this->assertEquals(array($this->twigExtension, 'getClassRoute'), $function->getCallable());

        /** @var \Twig_SimpleFunction $function */
        $function = $functions[5];
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertEquals('oro_entity_metadata_value', $function->getName());
        $this->assertEquals(array($this->twigExtension, 'getClassMetadataValue'), $function->getCallable());

        /** @var \Twig_SimpleFunction $function */
        $function = $functions[6];
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertEquals('oro_entity_view_link', $function->getName());
        $this->assertEquals(array($this->twigExtension, 'getViewLink'), $function->getCallable());
    }

    public function testGetName()
    {
        $this->assertEquals(ConfigExtension::NAME, $this->twigExtension->getName());
    }

    public function testGetClassConfigNoConfig()
    {
        $className = 'Test\Entity';

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->will($this->returnValue(false));
        $this->configManager->expects($this->never())
            ->method('getConfig');

        $this->assertEquals(array(), $this->twigExtension->getClassConfig($className));
    }

    public function testGetFieldConfigNoConfig()
    {
        $className = 'Test\Entity';
        $fieldName = 'testField';

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue(false));
        $this->configManager->expects($this->never())
            ->method('getProvider');

        $this->assertEquals([], $this->twigExtension->getFieldConfig($className, $fieldName));
    }

    /**
     * @param string|null $inputScope
     *
     * @dataProvider getFieldConfigDataProvider
     */
    public function testGetFieldConfig($inputScope)
    {
        $className = 'Test\Entity';
        $fieldName = 'testField';
        $config    = array('key' => 'value');

        $configEntity = $this->getMockForAbstractClass('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');

        $configEntity->expects($this->any())
            ->method('all')
            ->will($this->returnValue($config));

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue(true));


        $entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $scope = $inputScope ? $inputScope : 'entity';

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with($scope)
            ->willReturn($entityConfigProvider);

        $entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->willReturn($configEntity);

        if ($inputScope) {
            $actualConfig = $this->twigExtension->getFieldConfig($className, $fieldName, $inputScope);
        } else {
            $actualConfig = $this->twigExtension->getFieldConfig($className, $fieldName);
        }

        $this->assertEquals($config, $actualConfig);
    }

    public function getFieldConfigDataProvider()
    {
        return array(
            'default scope'   => array(
                'inputScope'    => null,
            ),
            'specified scope' => array(
                'inputScope'    => 'test',
            ),
        );
    }

    public function testGetFieldConfigValueNoConfig()
    {
        $className = 'Test\Entity';
        $fieldName = 'testField';

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue(false));
        $this->configManager->expects($this->never())
            ->method('getProvider');

        $this->assertNull($this->twigExtension->getFieldConfigValue($className, $fieldName, 'test'));
    }

    /**
     * @param string|null $inputScope
     *
     * @dataProvider getFieldConfigValueDataProvider
     */
    public function testGetFieldConfigValue($inputScope)
    {
        $className = 'Test\Entity';
        $fieldName = 'testField';
        $attrName  = 'attrName';
        $config    = array('key' => 'value');

        $configEntity = $this->getMockForAbstractClass('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');

        $configEntity->expects($this->any())
            ->method('get')
            ->with($attrName)
            ->will($this->returnValue($config));

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue(true));


        $entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $scope = $inputScope ? $inputScope : 'entity';

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with($scope)
            ->willReturn($entityConfigProvider);

        $entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->willReturn($configEntity);

        if ($inputScope) {
            $actualConfig = $this->twigExtension->getFieldConfigValue($className, $fieldName, $attrName, $inputScope);
        } else {
            $actualConfig = $this->twigExtension->getFieldConfigValue($className, $fieldName, $attrName);
        }

        $this->assertEquals($config, $actualConfig);
    }

    public function getFieldConfigValueDataProvider()
    {
        return array(
            'default scope'   => array(
                'inputScope'    => null,
            ),
            'specified scope' => array(
                'inputScope'    => 'test',
            ),
        );
    }

    /**
     * @param string      $expectedScope
     * @param string|null $inputScope
     *
     * @dataProvider getClassConfigDataProvider
     */
    public function testGetClassConfig($expectedScope, $inputScope)
    {
        $className = 'Test\Entity';
        $config    = array('key' => 'value');

        $configEntity = $this->getMockForAbstractClass('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $configEntity->expects($this->any())->method('all')->will($this->returnValue($config));

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->will($this->returnValue(true));
        $this->configManager->expects($this->once())
            ->method('getConfig')
            ->with($this->isInstanceOf('Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId'))
            ->will(
                $this->returnCallback(
                    function (EntityConfigId $configId) use ($className, $expectedScope, $configEntity) {
                        self::assertEquals($className, $configId->getClassName());
                        self::assertEquals($expectedScope, $configId->getScope());

                        return $configEntity;
                    }
                )
            );

        if ($inputScope) {
            $actualConfig = $this->twigExtension->getClassConfig($className, $inputScope);
        } else {
            $actualConfig = $this->twigExtension->getClassConfig($className);
        }
        $this->assertEquals($config, $actualConfig);
    }

    public function getClassConfigDataProvider()
    {
        return array(
            'default scope'   => array(
                'expectedScope' => 'entity',
                'inputScope'    => null,
            ),
            'specified scope' => array(
                'expectedScope' => 'test',
                'inputScope'    => 'test',
            ),
        );
    }

    public function testGetClassConfigValueNoConfig()
    {
        $className = 'Test\Entity';

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->will($this->returnValue(false));
        $this->configManager->expects($this->never())
            ->method('getConfig');

        $this->assertNull($this->twigExtension->getClassConfigValue($className, 'test'));
    }

    public function testGetClassConfigValue()
    {
        $className         = 'Test\Entity';
        $configEntityScope = new Config(new EntityConfigId('entity', $className));
        $configEntityScope->set('test', 'entity_val');
        $configAnotherScope = new Config(new EntityConfigId('another', $className));
        $configAnotherScope->set('test', 'another_val');

        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->with($className)
            ->will($this->returnValue(true));
        $this->configManager->expects($this->any())
            ->method('getConfig')
            ->with($this->isInstanceOf('Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId'))
            ->will(
                $this->returnCallback(
                    function (EntityConfigId $configId) use ($className, $configEntityScope, $configAnotherScope) {
                        self::assertEquals($className, $configId->getClassName());
                        switch ($configId->getScope()) {
                            case 'entity':
                                return $configEntityScope;
                            case 'another':
                                return $configAnotherScope;
                            default:
                                return null;
                        }
                    }
                )
            );

        // test default scope
        $this->assertEquals(
            'entity_val',
            $this->twigExtension->getClassConfigValue($className, 'test')
        );
        // test with specified scope
        $this->assertEquals(
            'another_val',
            $this->twigExtension->getClassConfigValue($className, 'test', 'another')
        );
        // test undefined attribute
        $this->assertNull(
            $this->twigExtension->getClassConfigValue($className, 'undefined')
        );
    }

    public function testGEtClassRouteNoConfig()
    {
        $className = 'Test\Entity';

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(false);
        $this->configManager->expects($this->never())
            ->method('getEntityMetadata');

        $this->assertNull($this->twigExtension->getClassRoute($className));
    }

    public function testGetClassRouteInNonStrictMode()
    {
        $className = 'Test\Entity';
        $viewRoute = 'route_view';

        $metadata = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()->getMock();
        $metadata->expects($this->once())->method('getRoute')
            ->with('view', false)->willReturn($viewRoute);

        $this->configManager->expects($this->once())->method('hasConfig')
            ->with($className)->willReturn(true);
        $this->configManager->expects($this->once())->method('getEntityMetadata')
            ->with($className)->willReturn($metadata);

        $this->assertSame($viewRoute, $this->twigExtension->getClassRoute($className));
    }

    public function testGetClassRouteShouldPassArgumentsToDelegatingMethod()
    {
        $className = 'Test\Entity';
        $createRoute = 'route_create';

        $metadata = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()->getMock();
        $metadata->expects($this->once())->method('getRoute')
            ->with('create', $strict = true)->willReturn($createRoute);

        $this->configManager->expects($this->once())->method('hasConfig')
            ->with($className)->willReturn(true);
        $this->configManager->expects($this->once())->method('getEntityMetadata')
            ->with($className)->willReturn($metadata);

        $this->assertSame($createRoute, $this->twigExtension->getClassRoute($className, 'create', $strict));
    }

    public function testGetClassMetadataValueNoConfig()
    {
        $className = 'Test\Entity';

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->will($this->returnValue(false));
        $this->configManager->expects($this->never())
            ->method('getConfig');

        $this->assertNull($this->twigExtension->getClassMetadataValue($className, 'test'));
    }

    public function testGetClassMetadataValueNoAttr()
    {
        $className         = 'Test\Entity';
        $configEntityScope = new Config(new EntityConfigId('entity', $className));
        $configEntityScope->set('test', 'entity_val');
        $configAnotherScope = new Config(new EntityConfigId('another', $className));
        $configAnotherScope->set('test', 'another_val');

        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->with($className)
            ->will($this->returnValue(true));

        $this->assertNull($this->twigExtension->getClassMetadataValue($className, 'test'));
    }

    public function testGetClassMetadataValue()
    {
        $className = 'Test\Entity';
        $attrName = 'routeView';
        $attrVal  = 'test_route';

        $metadata = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()->getMock();
        $reflection = new \ReflectionClass($metadata);
        $routeViewProp = $reflection->getProperty($attrName);
        $routeViewProp->setValue($metadata, $attrVal);


        $this->configManager
            ->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);
        $this->configManager
            ->expects($this->exactly(2))
            ->method('getEntityMetadata')
            ->with($className)
            ->willReturn($metadata);

        $this->assertSame($attrVal, $this->twigExtension->getClassMetadataValue($className, $attrName));
    }
}
