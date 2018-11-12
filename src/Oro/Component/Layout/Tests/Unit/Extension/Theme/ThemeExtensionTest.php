<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme;

use Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer;
use Oro\Component\Layout\Extension\Theme\PathProvider\ChainPathProvider;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\ResourceProviderInterface;
use Oro\Component\Layout\Extension\Theme\ThemeExtension;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutItem;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\Loader\Driver\DriverInterface;
use Oro\Component\Layout\Loader\LayoutUpdateLoader;
use Oro\Component\Layout\RawLayoutBuilder;

class ThemeExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ThemeExtension */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ChainPathProvider */
    protected $pathProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DriverInterface */
    protected $phpDriver;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DriverInterface */
    protected $yamlDriver;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DependencyInitializer */
    protected $dependencyInitializer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ResourceProviderInterface */
    protected $resourceProvider;

    protected function setUp()
    {
        $this->pathProvider = $this
            ->createMock('Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs\StubContextAwarePathProvider');
        $this->yamlDriver = $this
            ->getMockBuilder('Oro\Component\Layout\Loader\Driver\DriverInterface')
            ->setMethods(['load', 'getUpdateFilenamePattern'])
            ->getMock();
        $this->phpDriver = $this
            ->getMockBuilder('Oro\Component\Layout\Loader\Driver\DriverInterface')
            ->setMethods(['load', 'getUpdateFilenamePattern'])
            ->getMock();

        $this->dependencyInitializer = $this
            ->getMockBuilder('Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceProvider = $this
            ->createMock('Oro\Component\Layout\Extension\Theme\ResourceProvider\ResourceProviderInterface');

        $loader = new LayoutUpdateLoader();
        $loader->addDriver('yml', $this->yamlDriver);
        $loader->addDriver('php', $this->phpDriver);

        $this->extension = new ThemeExtension(
            $loader,
            $this->dependencyInitializer,
            $this->pathProvider,
            $this->resourceProvider
        );
    }

    public function testGetLayoutUpdates()
    {
        $themeName = 'my-theme';
        $this->pathProvider->expects($this->once())->method('getPaths')->willReturn([$themeName]);

        $this->resourceProvider
            ->expects($this->any())
            ->method('findApplicableResources')
            ->with([$themeName])
            ->will($this->returnValue([
                'oro-default/resource1.yml',
                'oro-default/page/resource2.yml',
                'oro-default/page/resource3.php'
            ]));

        $result = $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
        $this->assertEquals([], $result);
    }

    public function testThemeUpdatesFoundWithOneSkipped()
    {
        $themeName = 'oro-default';
        $this->pathProvider->expects($this->once())->method('getPaths')->willReturn([$themeName]);

        $this->resourceProvider
            ->expects($this->any())
            ->method('findApplicableResources')
            ->with([$themeName])
            ->will($this->returnValue([
                'oro-default/resource1.yml',
                'oro-default/page/resource3.php'
            ]));

        $updateMock = $this->createMock('Oro\Component\Layout\LayoutUpdateInterface');
        $update2Mock = $this->createMock('Oro\Component\Layout\LayoutUpdateInterface');

        $this->yamlDriver->expects($this->once())->method('load')
            ->with('oro-default/resource1.yml')
            ->willReturn($updateMock);
        $this->phpDriver->expects($this->once())->method('load')
            ->with('oro-default/page/resource3.php')
            ->willReturn($update2Mock);

        $result = $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
        $this->assertContains($updateMock, $result);
        $this->assertContains($update2Mock, $result);
    }

    public function testShouldPassDependenciesToUpdateInstance()
    {
        $themeName = 'oro-gold';
        $update = $this->createMock('Oro\Component\Layout\LayoutUpdateInterface');
        $this->pathProvider->expects($this->once())->method('getPaths')->willReturn([$themeName]);

        $this->resourceProvider
            ->expects($this->any())
            ->method('findApplicableResources')
            ->with([$themeName])
            ->will($this->returnValue([
                'oro-default/resource1.yml'
            ]));

        $this->yamlDriver->expects($this->once())->method('load')->willReturn($update);

        $this->dependencyInitializer->expects($this->once())->method('initialize')->with($this->identicalTo($update));

        $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
    }

    public function testShouldPassContextInContextAwareProvider()
    {
        $themeName = 'my-theme';
        $this->pathProvider->expects($this->once())->method('getPaths')->willReturn([$themeName]);

        $this->resourceProvider
            ->expects($this->any())
            ->method('findApplicableResources')
            ->with([$themeName])
            ->will($this->returnValue([
                'oro-default/resource1.yml',
                'oro-default/page/resource2.yml',
                'oro-default/page/resource3.php'
            ]));

        $this->pathProvider->expects($this->once())->method('setContext');

        $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
    }

    /**
     * @param string $id
     * @param null|string $theme
     *
     * @return LayoutItemInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getLayoutItem($id, $theme = null)
    {
        $context = new LayoutContext();
        $context->set('theme', $theme);
        $layoutItem = (new LayoutItem(new RawLayoutBuilder(), $context));
        $layoutItem->initialize($id);

        return $layoutItem;
    }
}
