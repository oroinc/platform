<?php

namespace Oro\Bundle\RequireJSBundle\Tests\Unit\Twig;

use Oro\Bundle\RequireJSBundle\Config\Config;
use Oro\Bundle\RequireJSBundle\Manager\ConfigProviderManager;
use Oro\Bundle\RequireJSBundle\Provider\ConfigProvider;
use Oro\Bundle\RequireJSBundle\Twig\OroRequireJSExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OroRequireJSExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var OroRequireJSExtension */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $config;

    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $container;

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

        $twig = $this->createMock(\Twig_Environment::class);

        $this->container = self::getContainerBuilder()
            ->add('oro_requirejs.config_provider.manager', $manager)
            ->add('twig', $twig)
            ->getContainer($this);

        $this->extension = new OroRequireJSExtension($this->container, './public/root', false);
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

    public function testGetRequireJSBuildLoggerReturnEmptyString()
    {
        $extension = new OroRequireJSExtension($this->container, './public/root', false);
        $result = $extension->getRequireJSBuildLogger();

        $this->assertEquals('', $result);
    }

    public function testGetRequireJSBuildLogger()
    {
        $extension = new OroRequireJSExtension($this->container, './public/root', true);

        $this->config->expects($this->once())
            ->method('getBuildConfig')
            ->willReturn(
                [
                    'paths' => [
                        'orosidebar/js/widget-container/templates/icon-template.html' => 'empty:',
                        'orocomment/templates/comment/comment-list-view.html' => 'empty:'
                    ]
                ]
            );

        $twig = $this->container->get('twig');
        $twig->expects($this->once())
            ->method('render')
            ->with(
                OroRequireJSExtension::BUILD_LOGGER_TEMPLATE,
                [
                    'excludeList' => [
                        'orosidebar/js/widget-container/templates/icon-template.html',
                        'orocomment/templates/comment/comment-list-view.html'
                    ]
                ]
            );

        $extension->getRequireJSBuildLogger();
    }
}
