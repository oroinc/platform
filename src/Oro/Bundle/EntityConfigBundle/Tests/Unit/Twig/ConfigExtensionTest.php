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
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->twigExtension = new ConfigExtension($this->configManager);
    }

    protected function tearDown()
    {
        unset($this->configManager);
        unset($this->twigExtension);
    }

    public function testGetFunctions()
    {
        $functions = $this->twigExtension->getFunctions();
        $this->assertCount(2, $functions);

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

    /**
     * @param string $expectedScope
     * @param string|null $inputScope
     * @dataProvider getClassConfigDataProvider
     */
    public function testGetClassConfig($expectedScope, $inputScope)
    {
        $className = 'Test\Entity';
        $config = array('key' => 'value');

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
            'default scope' => array(
                'expectedScope' => 'entity',
                'inputScope' => null,
            ),
            'specified scope' => array(
                'expectedScope' => 'test',
                'inputScope' => 'test',
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
        $className = 'Test\Entity';
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
}
