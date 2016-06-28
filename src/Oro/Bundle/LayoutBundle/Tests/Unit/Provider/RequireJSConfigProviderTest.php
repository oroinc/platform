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

    /**
     * @var string
     */
    protected $themeName = 'default';

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

        $class = new \ReflectionClass(RequireJSConfigProvider::class);
        $property = $class->getProperty('configKey');
        $property->setAccessible(true);
        $property->setValue($this->provider, $this->themeName);
    }

    public function testGetConfigFilePath()
    {
        $filePath = implode(
            [
                RequireJSConfigProvider::REQUIREJS_JS_DIR,
                $this->themeName,
                RequireJSConfigProvider::REQUIREJS_CONFIG_FILE
            ],
            DIRECTORY_SEPARATOR
        );

        $this->assertEquals($filePath, $this->provider->getConfigFilePath());
    }

    public function testGetOutputFilePath()
    {
        $buildPath = 'build/path';
        $config = [
            'config' => [
                'build_path' => $buildPath,
            ]
        ];

        $this->provider
            ->expects($this->once())
            ->method('collectConfigs')
            ->will($this->returnValue($config));

        $filePath = implode(
            [RequireJSConfigProvider::REQUIREJS_JS_DIR, $this->themeName, $buildPath],
            DIRECTORY_SEPARATOR
        );

        $this->assertEquals($filePath, $this->provider->getOutputFilePath($config));
    }

    public function testCollectAllConfigs()
    {
        $mainConfig = ['Theme Main Config'];
        $buildConfig = ['Theme Build Config'];

        $configs = [
            $this->themeName => [
                'mainConfig' => $mainConfig,
                'buildConfig' => $buildConfig
            ],
        ];

        $this->theme
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($this->themeName));

        $this->provider
            ->expects($this->once())
            ->method('generateMainConfig')
            ->will($this->returnValue($mainConfig));

        $this->provider
            ->expects($this->once())
            ->method('collectBuildConfig')
            ->will($this->returnValue($buildConfig));

        $this->assertEquals($configs, $this->provider->collectAllConfigs());
    }

    public function testCollectBuildConfig()
    {
        $config = ['config'];

        $this->provider
            ->expects($this->once())
            ->method('collectConfigs')
            ->will($this->returnValue($config));

        $this->provider
            ->expects($this->once())
            ->method('extractBuildConfig')
            ->with($config)
            ->will($this->returnValue($config));

        $class = new \ReflectionClass(RequireJSConfigProvider::class);
        $method = $class->getMethod('collectBuildConfig');
        $method->setAccessible(true);

        $this->assertEquals($config, $method->invoke($this->provider, $this->themeName));
    }

    public function testGetFiles()
    {
        $parentTheme = 'parent';
        $directory = 'directory';
        $bundle = 'Oro\Bundle\LayoutBundle\OroLayoutBundle';

        $this->themeManager
            ->expects($this->at(0))
            ->method('getTheme')
            ->with($this->themeName)
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

        $this->assertEquals([], $method->invoke($this->provider, $bundle, $this->themeName));
    }

    public function testGetTheme()
    {
        $this->themeManager
            ->expects($this->once())
            ->method('getTheme')
            ->with($this->themeName)
            ->will($this->returnValue($this->theme));

        $class = new \ReflectionClass(RequireJSConfigProvider::class);
        $method = $class->getMethod('getTheme');
        $method->setAccessible(true);

        $this->assertEquals($this->theme, $method->invoke($this->provider, $this->themeName));
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
