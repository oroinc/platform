<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme;

use Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer;
use Oro\Component\Layout\Extension\Theme\PathProvider\ChainPathProvider;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\ResourceProviderInterface;
use Oro\Component\Layout\Extension\Theme\ThemeExtension;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutItem;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutUpdateInterface;
use Oro\Component\Layout\Loader\Driver\DriverInterface;
use Oro\Component\Layout\Loader\LayoutUpdateLoader;
use Oro\Component\Layout\RawLayoutBuilder;
use Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs\StubContextAwarePathProvider;

class ThemeExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChainPathProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $pathProvider;

    /** @var DriverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $phpDriver;

    /** @var DriverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $yamlDriver;

    /** @var DependencyInitializer|\PHPUnit\Framework\MockObject\MockObject */
    private $dependencyInitializer;

    /** @var ResourceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $resourceProvider;

    /** @var ThemeExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->pathProvider = $this->createMock(StubContextAwarePathProvider::class);
        $this->yamlDriver = $this->createMock(DriverInterface::class);
        $this->phpDriver = $this->createMock(DriverInterface::class);
        $this->dependencyInitializer = $this->createMock(DependencyInitializer::class);
        $this->resourceProvider = $this->createMock(ResourceProviderInterface::class);

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
        $this->pathProvider->expects($this->once())
            ->method('getPaths')
            ->willReturn([$themeName]);

        $this->resourceProvider->expects($this->any())
            ->method('findApplicableResources')
            ->with([$themeName])
            ->willReturn([
                'oro-default/resource1.yml',
                'oro-default/page/resource2.yml',
                'oro-default/page/resource3.php'
            ]);

        $result = $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
        $this->assertEquals([], $result);
    }

    public function testThemeUpdatesFoundWithOneSkipped()
    {
        $themeName = 'oro-default';
        $this->pathProvider->expects($this->once())
            ->method('getPaths')
            ->willReturn([$themeName]);

        $this->resourceProvider->expects($this->any())
            ->method('findApplicableResources')
            ->with([$themeName])
            ->willReturn([
                'oro-default/resource1.yml',
                'oro-default/page/resource3.php'
            ]);

        $updateMock = $this->createMock(LayoutUpdateInterface::class);
        $update2Mock = $this->createMock(LayoutUpdateInterface::class);

        $this->yamlDriver->expects($this->once())
            ->method('load')
            ->with('oro-default/resource1.yml')
            ->willReturn($updateMock);
        $this->phpDriver->expects($this->once())
            ->method('load')
            ->with('oro-default/page/resource3.php')
            ->willReturn($update2Mock);

        $result = $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
        $this->assertContains($updateMock, $result);
        $this->assertContains($update2Mock, $result);
    }

    public function testShouldPassDependenciesToUpdateInstance()
    {
        $themeName = 'oro-gold';
        $update = $this->createMock(LayoutUpdateInterface::class);
        $this->pathProvider->expects($this->once())
            ->method('getPaths')
            ->willReturn([$themeName]);

        $this->resourceProvider->expects($this->any())
            ->method('findApplicableResources')
            ->with([$themeName])
            ->willReturn([
                'oro-default/resource1.yml'
            ]);

        $this->yamlDriver->expects($this->once())
            ->method('load')
            ->willReturn($update);

        $this->dependencyInitializer->expects($this->once())
            ->method('initialize')
            ->with($this->identicalTo($update));

        $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
    }

    public function testShouldPassContextInContextAwareProvider()
    {
        $themeName = 'my-theme';
        $this->pathProvider->expects($this->once())
            ->method('getPaths')
            ->willReturn([$themeName]);

        $this->resourceProvider->expects($this->any())
            ->method('findApplicableResources')
            ->with([$themeName])
            ->willReturn([
                'oro-default/resource1.yml',
                'oro-default/page/resource2.yml',
                'oro-default/page/resource3.php'
            ]);

        $this->pathProvider->expects($this->once())
            ->method('setContext');

        $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
    }

    private function getLayoutItem(string $id, string $theme = null): LayoutItemInterface
    {
        $context = new LayoutContext();
        $context->set('theme', $theme);
        $layoutItem = (new LayoutItem(new RawLayoutBuilder(), $context));
        $layoutItem->initialize($id);

        return $layoutItem;
    }
}
