<?php

namespace Oro\Bundle\RequireJSBundle\Tests\Unit\Twig;

use Oro\Bundle\RequireJSBundle\Config\Config;
use Oro\Bundle\RequireJSBundle\Manager\ConfigProviderManager;
use Oro\Bundle\RequireJSBundle\Provider\ConfigProvider;
use Oro\Bundle\RequireJSBundle\Twig\OroRequireJSExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class OroRequireJSExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var OroRequireJSExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $config;

    protected function setUp()
    {
        $this->config = $this->createMock(Config::class);
        $provider = $this->createMock(ConfigProvider::class);
        $provider->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($this->config));

        $manager = $this->createMock(ConfigProviderManager::class);
        $manager->expects($this->any())
            ->method('getProvider')
            ->with('oro_requirejs_config_provider')
            ->will($this->returnValue($provider));

        $container = self::getContainerBuilder()
            ->add('oro_requirejs.config_provider.manager', $manager)
            ->getContainer($this);

        $this->extension = new OroRequireJSExtension($container, './web/root', false);
    }

    public function testGetRequireJSConfig()
    {
        $config = ['Main Config'];
        $this->config
            ->expects($this->any())
            ->method('getMainConfig')
            ->will($this->returnValue($config));

        $this->assertEquals(
            $config,
            self::callTwigFunction($this->extension, 'get_requirejs_config', ['oro_requirejs_config_provider'])
        );
    }

    public function testGetRequireJSBuildPath()
    {
        $filePath = 'file/path';
        $this->config
            ->expects($this->once())
            ->method('getOutputFilePath')
            ->will($this->returnValue($filePath));

        $this->assertEquals(
            $filePath,
            self::callTwigFunction($this->extension, 'get_requirejs_build_path', ['oro_requirejs_config_provider'])
        );
    }

    public function testIsRequireJSBuildExists()
    {
        $filePath = 'file/path';
        $this->config
            ->expects($this->once())
            ->method('getOutputFilePath')
            ->will($this->returnValue($filePath));

        $this->assertFalse(
            self::callTwigFunction($this->extension, 'requirejs_build_exists', ['oro_requirejs_config_provider'])
        );
    }

    public function testGetRequireJSConfigNull()
    {
        $config = ['Main Config'];
        $this->config
            ->expects($this->any())
            ->method('getMainConfig')
            ->will($this->returnValue($config));

        $this->assertEquals(
            $config,
            self::callTwigFunction($this->extension, 'get_requirejs_config', [])
        );
    }

    public function testGetRequireJSBuildPathNull()
    {
        $filePath = 'file/path';
        $this->config
            ->expects($this->once())
            ->method('getOutputFilePath')
            ->will($this->returnValue($filePath));

        $this->assertEquals(
            $filePath,
            self::callTwigFunction($this->extension, 'get_requirejs_build_path', [])
        );
    }

    public function testIsRequireJSBuildExistsNull()
    {
        $filePath = 'file/path';
        $this->config
            ->expects($this->once())
            ->method('getOutputFilePath')
            ->will($this->returnValue($filePath));

        $this->assertFalse(
            self::callTwigFunction($this->extension, 'requirejs_build_exists', [])
        );
    }

    public function testGetName()
    {
        $this->assertEquals('requirejs_extension', $this->extension->getName());
    }
}
