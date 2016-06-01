<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme;

use Oro\Component\Layout\Loader\LayoutUpdateLoader;
use Oro\Component\Layout\Extension\Theme\PathProvider\ChainPathProvider;
use Oro\Component\Layout\Loader\Driver\DriverInterface;
use Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer;
use Oro\Component\Layout\Extension\Theme\ThemeExtension;

class ThemeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ThemeExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ChainPathProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DriverInterface */
    protected $phpDriver;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DriverInterface */
    protected $yamlDriver;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DependencyInitializer */
    protected $dependencyInitializer;

    /** @var array */
    protected $resources = [
        'oro-default' => [
            'resource1.yml',
            'resource2.xml',
            'resource3.php'
        ],
        'oro-gold'    => [
            'resource-gold.yml',
            'index' => [
                'resource-update.yml'
            ]
        ],
    ];

    protected function setUp()
    {
        $this->provider   = $this
            ->getMock('Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs\StubContextAwarePathProvider');
        $this->yamlDriver = $this
            ->getMockBuilder('Oro\Component\Layout\Loader\Driver\DriverInterface')
            ->setMethods(['supports', 'load'])
            ->getMock()
        ;
        $this->phpDriver  = $this
            ->getMockBuilder('Oro\Component\Layout\Loader\Driver\DriverInterface')
            ->setMethods(['supports', 'load'])
            ->getMock()
        ;

        $this->dependencyInitializer = $this
            ->getMockBuilder('Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer')
            ->disableOriginalConstructor()->getMock();

        $loader = new LayoutUpdateLoader();
        $loader->addDriver('yml', $this->yamlDriver);
        $loader->addDriver('php', $this->phpDriver);

        $this->extension = new ThemeExtension(
            $this->resources,
            $loader,
            $this->dependencyInitializer,
            $this->provider
        );
    }

    protected function tearDown()
    {
        unset(
            $this->extension,
            $this->provider,
            $this->yamlDriver,
            $this->phpDriver,
            $this->dependencyInitializer
        );
    }

    public function testThemeWithoutUpdatesTheme()
    {
        $themeName = 'my-theme';
        $this->setUpResourcePathProvider($themeName);

        $this->yamlDriver->expects($this->never())->method('supports');
        $this->phpDriver->expects($this->never())->method('supports');

        $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
    }

    public function testThemeYamlUpdateFound()
    {
        $themeName = 'oro-gold';
        $this->setUpResourcePathProvider($themeName);

        $callbackBuilder = $this->getCallbackBuilder();

        $this->yamlDriver->expects($this->any())->method('supports')
            ->willReturnCallback($callbackBuilder('yml'));
        $this->phpDriver->expects($this->never())->method('supports')
            ->willReturnCallback($callbackBuilder('php'));

        $updateMock = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');

        $this->yamlDriver->expects($this->once())->method('load')
            ->with('resource-gold.yml')
            ->willReturn($updateMock);

        $result = $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
        $this->assertContains($updateMock, $result);
    }

    public function testUpdatesFoundBasedOnMultiplePaths()
    {
        $themeName = 'oro-gold';
        $this->setUpResourcePathProvider([$themeName], [$themeName . '_index']);

        $callbackBuilder = $this->getCallbackBuilder();

        $this->yamlDriver->expects($this->any())->method('supports')
            ->willReturnCallback($callbackBuilder('yml'));
        $this->phpDriver->expects($this->never())->method('supports')
            ->willReturnCallback($callbackBuilder('php'));

        $updateMock = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');

        $this->yamlDriver->expects($this->once())->method('load')
            ->with('resource-gold.yml')
            ->willReturn($updateMock);

        $result = $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
        $this->assertContains($updateMock, $result);
    }

    public function testThemeUpdatesFoundWithOneSkipped()
    {
        $themeName = 'oro-default';
        $this->setUpResourcePathProvider($themeName);

        $callbackBuilder = $this->getCallbackBuilder();

        $this->yamlDriver->expects($this->any())->method('supports')
            ->willReturnCallback($callbackBuilder('yml'));
        $this->phpDriver->expects($this->any())->method('supports')
            ->willReturnCallback($callbackBuilder('php'));

        $updateMock  = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');
        $update2Mock = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');

        $this->yamlDriver->expects($this->once())->method('load')
            ->with('resource1.yml')
            ->willReturn($updateMock);
        $this->phpDriver->expects($this->once())->method('load')
            ->with('resource3.php')
            ->willReturn($update2Mock);

        $result = $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
        $this->assertContains($updateMock, $result);
        $this->assertContains($update2Mock, $result);
    }

    public function testShouldPassDependenciesToUpdateInstance()
    {
        $themeName = 'oro-gold';
        $update    = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');
        $this->setUpResourcePathProvider($themeName);

        $callbackBuilder = $this->getCallbackBuilder();
        $this->yamlDriver->expects($this->any())->method('supports')->willReturnCallback($callbackBuilder('yml'));
        $this->yamlDriver->expects($this->once())->method('load')->willReturn($update);

        $this->dependencyInitializer->expects($this->once())->method('initialize')->with($this->identicalTo($update));

        $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
    }

    public function testShouldPassContextInContextAwareProvider()
    {
        $themeName = 'my-theme';
        $this->setUpResourcePathProvider($themeName);

        $this->provider->expects($this->once())->method('setContext');

        $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
    }

    protected function getCallbackBuilder()
    {
        return function ($extension) {
            return function ($fileName) use ($extension) {
                return substr($fileName, -strlen($extension)) === $extension;
            };
        };
    }

    /**
     * @param array $paths
     */
    protected function setUpResourcePathProvider($paths)
    {
        $this->provider->expects($this->any())->method('getPaths')->willReturn((array)$paths);
    }

    /**
     * @param string      $id
     * @param null|string $theme
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getLayoutItem($id, $theme = null)
    {
        $layoutItem = $this->getMock('Oro\Component\Layout\LayoutItemInterface');
        $context    = $this->getMock('Oro\Component\Layout\ContextInterface');

        $layoutItem->expects($this->any())->method('getId')->willReturn($id);
        $layoutItem->expects($this->any())->method('getContext')->willReturn($context);

        $context->expects($this->any())->method('getOr')
            ->with('theme')->willReturn($theme);

        return $layoutItem;
    }
}
