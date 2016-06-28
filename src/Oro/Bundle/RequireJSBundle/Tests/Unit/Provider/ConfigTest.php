<?php

namespace Oro\Bundle\RequireJSBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\PhpFileCache;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\RequireJSBundle\Provider\Config as ConfigProvider;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface
     */
    protected $container;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EngineInterface
     */
    protected $templateEngine;

    /**
     * @var string
     */
    protected $template = '';

    protected function setUp()
    {
        $this->container = $this->getMockContainerInterface();
        $this->templateEngine = $this->getMockEngineInterface();
        $this->provider = new ConfigProvider($this->container, $this->templateEngine, $this->template);
    }

    /**
     * @dataProvider parametersProvider
     *
     * @param array $parameters
     */
    public function testGetOutputFilePath(array $parameters = [])
    {
        $this->container
            ->expects($this->any())
            ->method('getParameter')
            ->will($this->returnCallback(
                function ($name) use (&$parameters) {
                    return $parameters[$name];
                }
            ));

        $this->assertEquals(
            $parameters['oro_require_js']['build_path'],
            $this->provider->getOutputFilePath()
        );
    }

    public function testGetConfigFilePath()
    {
        $this->assertEquals(
            ConfigProvider::REQUIREJS_CONFIG_FILE,
            $this->provider->getConfigFilePath()
        );
    }

    /**
     * @dataProvider parametersProvider
     *
     * @param array $parameters
     */
    public function testGetMainConfig(array $parameters = [])
    {
        $configKey = '_main';
        $renderedConfig = '{}';
        $config = [
            'config' => [
                'paths' => [
                    'oro/test' => 'orosecondtest/js/second-test'
                ]
            ]
        ];

        $this->container
            ->expects($this->any())
            ->method('getParameter')
            ->will($this->returnCallback(
                function ($name) use (&$parameters) {
                    return $parameters[$name];
                }
            ));

        $this->templateEngine
            ->expects($this->any())
            ->method('render')
            ->with($this->template, $config)
            ->will($this->returnValue($renderedConfig));

        $mainConfig = $this->provider->getMainConfig();

        $this->assertEquals($renderedConfig, $mainConfig);

        $cache = $this->getMockPhpFileCache();
        $this->provider->setCache($cache);

        $allConfigs = [$configKey => ['mainConfig' => $mainConfig]];
        $cache->expects($this->once())
            ->method('fetch')
            ->with(ConfigProvider::REQUIREJS_CONFIG_CACHE_KEY)
            ->will($this->returnValue($allConfigs));

        $this->assertEquals($renderedConfig, $this->provider->getMainConfig());
    }

    /**
     * @dataProvider parametersProvider
     *
     * @param array $parameters
     */
    public function testCollectAllConfigs(array $parameters = [])
    {
        $configKey = '_main';

        $this->container
            ->expects($this->any())
            ->method('getParameter')
            ->will($this->returnCallback(
                function ($name) use (&$parameters) {
                    return $parameters[$name];
                }
            ));

        $allConfigs = $this->provider->collectAllConfigs();
        $this->assertArrayHasKey('mainConfig', $allConfigs[$configKey]);
        $this->assertArrayHasKey('buildConfig', $allConfigs[$configKey]);
    }

    /**
     * @dataProvider parametersProvider
     *
     * @param array $parameters
     */
    public function testGenerateMainConfig(array $parameters = [])
    {
        $renderedConfig = '{}';
        $config = [
            'config' => [
                'paths' => [
                    'oro/test' => 'orosecondtest/js/second-test'
                ]
            ]
        ];

        $this->container
            ->expects($this->any())
            ->method('getParameter')
            ->will($this->returnCallback(
                function ($name) use (&$parameters) {
                    return $parameters[$name];
                }
            ));

        $this->templateEngine
            ->expects($this->any())
            ->method('render')
            ->with($this->template, $config)
            ->will($this->returnValue($renderedConfig));

        $this->assertEquals($renderedConfig, $this->provider->generateMainConfig());
    }

    /**
     * @dataProvider parametersProvider
     *
     * @param array $parameters
     */
    public function testCollectBuildConfig(array $parameters = [])
    {
        $webRoot = $parameters['oro_require_js.web_root'];
        $buildPath = $parameters['oro_require_js']['build_path'];
        $config = [
            'paths' => [
                'oro/test' => 'empty:',
                'require-config' => $webRoot . '/js/require-config',
                'require-lib' => 'ororequirejs/lib/require',
            ],
            'baseUrl' => $webRoot . '/bundles',
            'out' => $webRoot . '/' . $buildPath,
            'mainConfigFile' => $webRoot . '/js/require-config.js',
            'include' => ['require-config', 'require-lib', 'oro/test']
        ];

        $this->container
            ->expects($this->any())
            ->method('getParameter')
            ->will($this->returnCallback(
                function ($name) use (&$parameters) {
                    return $parameters[$name];
                }
            ));

        $class = new \ReflectionClass(ConfigProvider::class);
        $method = $class->getMethod('collectBuildConfig');
        $method->setAccessible(true);

        $this->assertEquals($config, $method->invoke($this->provider));
    }

    /**
     * @dataProvider parametersProvider
     *
     * @param array $parameters
     */
    public function collectConfigs(array $parameters = [])
    {
        $this->container
            ->expects($this->any())
            ->method('getParameter')
            ->will($this->returnCallback(
                function ($name) use (&$parameters) {
                    return $parameters[$name];
                }
            ));

        $this->assertEquals($parameters, $this->provider->collectConfigs());
    }

    public function getCacheKey()
    {
        $class = new \ReflectionClass(ConfigProvider::class);
        $method = $class->getMethod('getCacheKey');
        $method->setAccessible(true);

        $this->assertEquals(
            ConfigProvider::REQUIREJS_CONFIG_CACHE_KEY,
            $method->invoke($this->provider)
        );
    }

    /**
     * @return array
     */
    public function parametersProvider()
    {
        return [
            [
                [
                    'oro_require_js' => [
                        'build_path' => 'build/path'
                    ],
                    'oro_require_js.web_root' => 'web/root',
                    'kernel.bundles' => [
                        'Oro\Bundle\RequireJSBundle\Tests\Unit\Fixtures\TestBundle\TestBundle',
                        'Oro\Bundle\RequireJSBundle\Tests\Unit\Fixtures\SecondTestBundle\SecondTestBundle',
                    ]
                ]
            ]
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ContainerInterface
     */
    protected function getMockContainerInterface()
    {
        return $this->getMockForAbstractClass('Symfony\Component\DependencyInjection\ContainerInterface');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EngineInterface
     */
    protected function getMockEngineInterface()
    {
        return $this->getMock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PhpFileCache
     */
    protected function getMockPhpFileCache()
    {
        return $this->getMock('Doctrine\Common\Cache\PhpFileCache', [], [], '', false);
    }
}
