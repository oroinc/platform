<?php

namespace LayoutBundle\Tests\Unit\Provider;

use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Bundle\LayoutBundle\Provider\RequireJSConfigProvider;

class RequireJSConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RequireJSConfigProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ThemeManager
     */
    protected $themeManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Theme
     */
    protected $theme;

    protected function setUp()
    {
        $this->theme = $this->getThemeMock();
        $this->themeManager = $this->getThemeManagerMock();
        $this->themeManager
            ->expects($this->any())
            ->method('getAllThemes')
            ->will($this->returnValue([$this->theme]));

        $this->provider = $this->getRequireJSConfigProviderMock();
        $this->provider
            ->expects($this->any())
            ->method('getThemeManager')
            ->will($this->returnValue($this->themeManager));
    }

    public function testGetConfigFilePath()
    {
        $theme = 'default';
        $config = [
            'config' => [
                'config_key' => $theme
            ]
        ];

        $this->assertEquals(
            $theme . DIRECTORY_SEPARATOR . RequireJSConfigProvider::REQUIREJS_CONFIG_FILE,
            $this->provider->getConfigFilePath($config)
        );
    }

    public function testGetOutputFilePath()
    {
        $theme = 'default';
        $buildPath = './web';
        $config = [
            'config' => [
                'config_key' => $theme,
                'build_path' => $buildPath,
            ]
        ];

        $this->assertEquals(
            $theme . DIRECTORY_SEPARATOR . $buildPath,
            $this->provider->getOutputFilePath($config)
        );
    }

    public function testCollectAllConfigs()
    {
        $theme = 'default';
        $mainConfig = ['Theme Main Config'];
        $buildConfig = ['Theme Build Config'];

        $configs = [
            $theme => [
                'mainConfig' => $mainConfig,
                'buildConfig' => $buildConfig
            ],
        ];

        $this->theme
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($theme));

        $this->provider
            ->expects($this->once())
            ->method('generateMainConfig')
            ->with($theme)
            ->will($this->returnValue($mainConfig));

        $this->provider
            ->expects($this->once())
            ->method('collectBuildConfig')
            ->with($theme)
            ->will($this->returnValue($buildConfig));

        $this->assertEquals($configs, $this->provider->collectAllConfigs());
    }

    public function testCollectBuildConfig()
    {
        $theme = 'default';
        $config = ['config'];

        $this->provider
            ->expects($this->once())
            ->method('collectConfigs')
            ->with($theme)
            ->will($this->returnValue($config));

        $this->provider
            ->expects($this->once())
            ->method('extractBuildConfig')
            ->with($config)
            ->will($this->returnValue($config));

        $class = new \ReflectionClass(RequireJSConfigProvider::class);
        $method = $class->getMethod('collectBuildConfig');
        $method->setAccessible(true);

        $this->assertEquals($config, $method->invoke($this->provider, $theme));
    }

    public function testGetFiles()
    {
        $theme = 'default';
        $parentTheme = 'parent';
        $directory = 'directory';
        $bundle = 'Oro\Bundle\LayoutBundle\OroLayoutBundle';

        $this->themeManager
            ->expects($this->at(0))
            ->method('getTheme')
            ->with($theme)
            ->will($this->returnValue($this->theme));

        $this->themeManager
            ->expects($this->at(1))
            ->method('getTheme')
            ->with($parentTheme)
            ->will($this->returnValue($this->theme));

        $this->theme
            ->expects($this->at(0))
            ->method('getParentTheme')
            ->will($this->returnValue($parentTheme));

        $this->theme
            ->expects($this->at(1))
            ->method('getParentTheme')
            ->will($this->returnValue($parentTheme));

        $this->theme
            ->expects($this->at(2))
            ->method('getParentTheme')
            ->will($this->returnValue(null));

        $this->theme
            ->expects($this->any())
            ->method('getDirectory')
            ->will($this->returnValue($directory));


        $class = new \ReflectionClass(RequireJSConfigProvider::class);
        $method = $class->getMethod('getFiles');
        $method->setAccessible(true);

        $this->assertEquals([], $method->invoke($this->provider, $bundle, $theme));
    }

    public function testGetTheme()
    {
        $theme = 'default';

        $this->themeManager
            ->expects($this->once())
            ->method('getTheme')
            ->with($theme)
            ->will($this->returnValue($this->theme));

        $class = new \ReflectionClass(RequireJSConfigProvider::class);
        $method = $class->getMethod('getTheme');
        $method->setAccessible(true);

        $this->assertEquals($this->theme, $method->invoke($this->provider, $theme));
    }

    public function testGetAllThemes()
    {
        $class = new \ReflectionClass(RequireJSConfigProvider::class);
        $method = $class->getMethod('getAllThemes');
        $method->setAccessible(true);

        $this->assertEquals([$this->theme], $method->invoke($this->provider));
    }

    public function testGetCacheKey()
    {
        $class = new \ReflectionClass(RequireJSConfigProvider::class);
        $method = $class->getMethod('getCacheKey');
        $method->setAccessible(true);

        $this->assertEquals(
            RequireJSConfigProvider::REQUIREJS_CONFIG_CACHE_KEY,
            $method->invoke($this->provider)
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RequireJSConfigProvider
     */
    protected function getRequireJSConfigProviderMock()
    {
        return $this->getMock('Oro\Bundle\LayoutBundle\Provider\RequireJSConfigProvider', [
            'getThemeManager', 'generateMainConfig', 'collectBuildConfig', 'collectConfigs',
            'extractBuildConfig'
        ], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ThemeManager
     */
    protected function getThemeManagerMock()
    {
        return $this->getMock('Oro\Component\Layout\Extension\Theme\Model\ThemeManager', [
            'getAllThemes', 'getTheme'
        ], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Theme
     */
    protected function getThemeMock()
    {
        return $this->getMock('Oro\Component\Layout\Extension\Theme\Model\Theme', [], [], '', false);
    }
}
