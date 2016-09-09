<?php

namespace Oro\Bundle\RequireJSBundle\Tests\Unit\Twig;

use Oro\Bundle\RequireJSBundle\Config\Config;
use Oro\Bundle\RequireJSBundle\Manager\ConfigProviderManager;
use Oro\Bundle\RequireJSBundle\Provider\ConfigProvider;
use Oro\Bundle\RequireJSBundle\Twig\OroRequireJSExtension;

class OroRequireJSExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroRequireJSExtension
     */
    protected $twigExtension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Config
     */
    protected $config;

    protected function setUp()
    {
        $this->config = $this->getMock('Oro\Bundle\RequireJSBundle\Config\Config');

        $provider = $this->getMock('Oro\Bundle\RequireJSBundle\Provider\ConfigProvider', [], [], '', false);
        $provider->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($this->config));

        $manager = $this->getMock('Oro\Bundle\RequireJSBundle\Manager\ConfigProviderManager');
        $manager->expects($this->any())
            ->method('getProvider')
            ->with('oro_requirejs_config_provider')
            ->will($this->returnValue($provider));

        $this->twigExtension = new OroRequireJSExtension($manager, './web/root');
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
        $this->config
            ->expects($this->any())
            ->method('getMainConfig')
            ->will($this->returnValue($config));

        $this->assertEquals($config, $this->twigExtension->getRequireJSConfig('oro_requirejs_config_provider'));
    }

    public function testGetRequireJSBuildPath()
    {
        $filePath = 'file/path';
        $this->config
            ->expects($this->once())
            ->method('getOutputFilePath')
            ->will($this->returnValue($filePath));

        $this->assertEquals($filePath, $this->twigExtension->getRequireJSBuildPath('oro_requirejs_config_provider'));
    }

    public function testIsRequireJSBuildExists()
    {
        $filePath = 'file/path';
        $this->config
            ->expects($this->once())
            ->method('getOutputFilePath')
            ->will($this->returnValue($filePath));

        $this->assertFalse($this->twigExtension->isRequireJSBuildExists('oro_requirejs_config_provider'));
    }

    public function testGetRequireJSConfigNull()
    {
        $config = ['Main Config'];
        $this->config
            ->expects($this->any())
            ->method('getMainConfig')
            ->will($this->returnValue($config));

        $this->assertEquals($config, $this->twigExtension->getRequireJSConfig());
    }

    public function testGetRequireJSBuildPathNull()
    {
        $filePath = 'file/path';
        $this->config
            ->expects($this->once())
            ->method('getOutputFilePath')
            ->will($this->returnValue($filePath));

        $this->assertEquals($filePath, $this->twigExtension->getRequireJSBuildPath());
    }

    public function testIsRequireJSBuildExistsNull()
    {
        $filePath = 'file/path';
        $this->config
            ->expects($this->once())
            ->method('getOutputFilePath')
            ->will($this->returnValue($filePath));

        $this->assertFalse($this->twigExtension->isRequireJSBuildExists());
    }

    public function testGetName()
    {
        $this->assertEquals('requirejs_extension', $this->twigExtension->getName());
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
}
