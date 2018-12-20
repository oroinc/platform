<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder;
use Oro\Bundle\LayoutBundle\Provider\RequireJSConfigProvider;
use Oro\Bundle\RequireJSBundle\Config\Config;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\LayoutContext;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class RequireJSConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RequireJSConfigProvider
     */
    protected $provider;

    /**
     * @var EngineInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $engineInterface;

    /**
     * @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $cache;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $webRoot;

    /**
     * @var ThemeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $themeManager;

    protected function setUp()
    {
        $this->engineInterface = $this->createMock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface');
        $this->cache = $this->createMock('Doctrine\Common\Cache\CacheProvider');

        $this->config = [
            'build_path' => './build/path',
            'config' => [
                'paths' => [
                    'oro/test' => 'test/js/test'
                ]
            ],
            'build' => [
                'paths' => [
                    'oro/test' => 'empty:'
                ]
            ]
        ];

        $this->webRoot = './public/root';

        $this->provider = new RequireJSConfigProvider(
            $this->engineInterface,
            $this->cache,
            $this->config,
            [
                'Oro\Bundle\RequireJSBundle\Tests\Unit\Fixtures\TestBundle\TestBundle'
            ],
            $this->webRoot
        );

        $this->themeManager = $this->createMock(
            'Oro\Component\Layout\Extension\Theme\Model\ThemeManager',
            [],
            [],
            '',
            false
        );

        $this->provider->setThemeManager($this->themeManager);
    }

    public function testGetConfig()
    {
        $requireConfig = [
            'require-config' => './public/root/js/layout/default/require-config',
            'require-lib' => 'npmassets/requirejs/require'
        ];

        $path = 'js/layout/default/';
        $mainConfigFile = $this->webRoot . DIRECTORY_SEPARATOR . $path . RequireJSConfigProvider::REQUIREJS_CONFIG_FILE;

        $config = new Config();
        $config->setBuildConfig([
            'paths' => array_merge($this->config['build']['paths'], $requireConfig),
            'baseUrl' => $this->webRoot . DIRECTORY_SEPARATOR . 'bundles',
            'out' => $this->webRoot . DIRECTORY_SEPARATOR . $path . $this->config['build_path'],
            'mainConfigFile' => $mainConfigFile,
            'include' => array_merge(array_keys($requireConfig), array_keys($this->config['config']['paths']))
        ]);
        $config->setOutputFilePath($path . $this->config['build_path']);
        $config->setConfigFilePath($path . RequireJSConfigProvider::REQUIREJS_CONFIG_FILE);

        /** @var Theme|\PHPUnit\Framework\MockObject\MockObject $theme */
        $theme = $this->createMock('Oro\Component\Layout\Extension\Theme\Model\Theme');
        $theme->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('default'));

        $this->themeManager
            ->expects($this->any())
            ->method('getTheme')
            ->with('default')
            ->will($this->returnValue($theme));

        $this->themeManager
            ->expects($this->once())
            ->method('getAllThemes')
            ->will($this->returnValue([$theme]));

        $this->cache
            ->expects($this->once())
            ->method('fetch')
            ->with(RequireJSConfigProvider::REQUIREJS_CONFIG_CACHE_KEY)
            ->will($this->returnValue(false));

        $this->cache
            ->expects($this->once())
            ->method('save')
            ->with(RequireJSConfigProvider::REQUIREJS_CONFIG_CACHE_KEY, ['default' => $config]);

        /** @var LayoutContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(LayoutContext::class);
        $context->expects($this->once())
            ->method('get')
            ->with('theme')
            ->will($this->returnValue('default'));

        $contextHolder = new LayoutContextHolder();
        $contextHolder->setContext($context);

        $this->provider->setContextHolder($contextHolder);
        $this->assertEquals($config, $this->provider->getConfig());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetConfigInvalidArgumentException()
    {
        $this->cache
            ->expects($this->once())
            ->method('fetch')
            ->with(RequireJSConfigProvider::REQUIREJS_CONFIG_CACHE_KEY)
            ->will($this->returnValue([]));

        $contextHolder = new LayoutContextHolder();

        $this->provider->setContextHolder($contextHolder);
        $this->provider->getConfig();
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testGetConfigOutOfBoundsException()
    {
        $this->cache
            ->expects($this->once())
            ->method('fetch')
            ->with(RequireJSConfigProvider::REQUIREJS_CONFIG_CACHE_KEY)
            ->will($this->returnValue(['default' => []]));

        /** @var LayoutContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(LayoutContext::class);
        $context->expects($this->once())
            ->method('get')
            ->with('theme')
            ->will($this->returnValue('another'));

        $contextHolder = new LayoutContextHolder();
        $contextHolder->setContext($context);

        $this->provider->setContextHolder($contextHolder);
        $this->provider->getConfig();
    }
}
