<?php

namespace Oro\Bundle\RequireJSBundle\Tests\Unit\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\RequireJSBundle\Twig\OroRequireJSExtension;
use Oro\Bundle\RequireJSBundle\Provider\ChainConfigProvider;
use Oro\Bundle\RequireJSBundle\Provider\Config as ConfigProvider;

class OroRequireJSExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroRequireJSExtension
     */
    protected $twigExtension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider
     */
    protected $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface
     */
    protected $container;

    protected function setUp()
    {
        $this->configProvider = $this->getMockConfigProvider();

        $chainConfigProvider = new ChainConfigProvider();
        $chainConfigProvider->addProvider($this->configProvider);

        $this->container = $this->getMockContainerInterface();
        $this->container->expects($this->any())
            ->method('get')
            ->with('oro_requirejs.config_provider.chain')
            ->will($this->returnValue($chainConfigProvider));

        $this->twigExtension = new OroRequireJSExtension($this->container);
    }

    /**
     * @dataProvider expectedFunctionsProvider
     *
     * @param string $keyName
     * @param string $functionName
     */
    public function testGetFunctions($keyName, $functionName)
    {
        $actualFunctions = $this->twigExtension->getFunctions();
        /** @var \Twig_SimpleFunction $function */
        $function = $actualFunctions[$keyName];

        $this->assertArrayHasKey($keyName, $actualFunctions);
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertEquals(
            [$this->twigExtension, $functionName],
            $function->getCallable()
        );
    }

    public function testGetRequireJSConfig()
    {
        $config = ['Main Config'];
        $this->configProvider
            ->expects($this->any())
            ->method('getMainConfig')
            ->will($this->returnValue($config));

        $this->assertEquals($config, $this->twigExtension->getRequireJSConfig());
    }

    public function testGetRequireJSBuildPath()
    {
        $config = ['Main Config'];
        $this->configProvider
            ->expects($this->any())
            ->method('getMainConfig')
            ->will($this->returnValue($config));

        $filePath = 'file/path';
        $this->configProvider
            ->expects($this->once())
            ->method('getOutputFilePath')
            ->will($this->returnValue($filePath));

        $this->assertEquals($filePath, $this->twigExtension->getRequireJSBuildPath());
    }

    public function testIsRequireJSBuildExists()
    {
        $config = ['Main Config'];
        $this->configProvider
            ->expects($this->any())
            ->method('getMainConfig')
            ->will($this->returnValue($config));

        $filePath = 'file/path';
        $this->configProvider
            ->expects($this->once())
            ->method('getOutputFilePath')
            ->will($this->returnValue($filePath));

        $webRoot = 'web/root';
        $this->container
            ->expects($this->any())
            ->method('getParameter')
            ->with('oro_require_js.web_root')
            ->will($this->returnValue($webRoot));

        $this->assertFalse($this->twigExtension->isRequireJSBuildExists());
    }

    public function testGetRequireJSConfigProvider()
    {
        $config = ['Main Config'];
        $this->configProvider
            ->expects($this->any())
            ->method('getMainConfig')
            ->will($this->returnValue($config));

        $class = new \ReflectionClass(OroRequireJSExtension::class);
        $method = $class->getMethod('getRequireJSConfigProvider');
        $method->setAccessible(true);

        $this->assertEquals($this->configProvider, $method->invoke($this->twigExtension, 'configKey'));
    }

    public function testGetRequireJSConfigProviderEmpty()
    {
        $class = new \ReflectionClass(OroRequireJSExtension::class);
        $method = $class->getMethod('getRequireJSConfigProvider');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($this->twigExtension, 'configKey'));
    }

    public function testGetName()
    {
        $extension = new OroRequireJSExtension($this->getMockContainerInterface());
        $this->assertEquals('requirejs_extension', $extension->getName());
    }

    /**
     * @return array
     */
    public function expectedFunctionsProvider()
    {
        return [
            ['get_requirejs_config', 'getRequireJSConfig'],
            ['get_requirejs_build_path', 'getRequireJSBuildPath'],
            ['requirejs_build_exists', 'isRequireJSBuildExists'],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ContainerInterface
     */
    protected function getMockContainerInterface()
    {
        return $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
    }


    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigProvider
     */
    protected function getMockConfigProvider()
    {
        return $this->getMock('Oro\Bundle\RequireJSBundle\Provider\Config', [], [], '', false);
    }
}
