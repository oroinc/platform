<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

use Oro\Bundle\LayoutBundle\Provider\RequireJSConfigProvider;
use Oro\Bundle\RequireJSBundle\Config\Config;

use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

class RequireJSConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequireJSConfigProvider
     */
    protected $provider;

    /**
     * @var EngineInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $engineInterface;

    /**
     * @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject
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
     * @var ThemeManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $themeManager;

    protected function setUp()
    {
        $this->engineInterface = $this->getMock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface');
        $this->cache = $this->getMock('Doctrine\Common\Cache\CacheProvider');

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

        $this->webRoot = './web/root';

        $this->provider = new RequireJSConfigProvider(
            $this->engineInterface,
            $this->cache,
            $this->config,
            [
                'Oro\Bundle\RequireJSBundle\Tests\Unit\Fixtures\TestBundle\TestBundle'
            ],
            $this->webRoot
        );

        $this->themeManager = $this->getMock(
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
            'require-config' => './web/root/js/layout/default/require-config',
            'require-lib' => 'ororequirejs/lib/require'
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

        /** @var Theme|\PHPUnit_Framework_MockObject_MockObject $theme */
        $theme = $this->getMock('Oro\Component\Layout\Extension\Theme\Model\Theme', [], [], '', false);
        $theme->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('default'));

        $theme->expects($this->any())
            ->method('getParentTheme')
            ->will($this->returnValue(null));

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
            ->method('contains')
            ->with(RequireJSConfigProvider::REQUIREJS_CONFIG_CACHE_KEY)
            ->will($this->returnValue(false));

        $this->cache
            ->expects($this->once())
            ->method('save')
            ->with(RequireJSConfigProvider::REQUIREJS_CONFIG_CACHE_KEY, ['default' => $config]);

        $this->cache
            ->expects($this->once())
            ->method('fetch')
            ->with(RequireJSConfigProvider::REQUIREJS_CONFIG_CACHE_KEY)
            ->will($this->returnValue(['default' => $config]));

        $this->provider->setActiveTheme('default');
        $this->assertEquals($config, $this->provider->getConfig());
    }
}
