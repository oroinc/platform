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
    private ChainPathProvider|\PHPUnit\Framework\MockObject\MockObject $pathProvider;

    private DriverInterface|\PHPUnit\Framework\MockObject\MockObject $phpDriver;

    private DriverInterface|\PHPUnit\Framework\MockObject\MockObject $yamlDriver;

    private DependencyInitializer|\PHPUnit\Framework\MockObject\MockObject $dependencyInitializer;

    private ResourceProviderInterface|\PHPUnit\Framework\MockObject\MockObject $resourceProvider;

    private ThemeExtension $extension;

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

    public function testGetLayoutUpdatesWhenNoUpdates(): void
    {
        $themeName = 'my-theme';
        $this->pathProvider->expects(self::once())
            ->method('getPaths')
            ->willReturn([$themeName]);

        $this->resourceProvider->expects(self::any())
            ->method('findApplicableResources')
            ->with([$themeName])
            ->willReturn([
                'oro-default/resource1.yml',
                'oro-default/page/resource2.yml',
                'oro-default/page/resource3.php',
            ]);

        $result = $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
        self::assertEquals([], $result);
    }

    public function testGetLayoutWhenFoundUpdates(): void
    {
        $themeName = 'oro-default';
        $this->pathProvider->expects(self::once())
            ->method('getPaths')
            ->willReturn([$themeName]);

        $this->resourceProvider->expects(self::any())
            ->method('findApplicableResources')
            ->with([$themeName])
            ->willReturn([
                'oro-default/resource1.yml',
                'oro-default/page/resource3.php',
            ]);

        $updateMock = $this->createMock(LayoutUpdateInterface::class);
        $update2Mock = $this->createMock(LayoutUpdateInterface::class);

        $this->yamlDriver->expects(self::once())
            ->method('load')
            ->with('oro-default/resource1.yml')
            ->willReturn($updateMock);
        $this->phpDriver->expects(self::once())
            ->method('load')
            ->with('oro-default/page/resource3.php')
            ->willReturn($update2Mock);

        $result = $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
        self::assertContains($updateMock, $result);
        self::assertContains($update2Mock, $result);
    }

    public function testGetLayoutWhenFoundDifferentContext(): void
    {
        $themeName1 = 'oro-default';
        $themeName2 = 'oro-custom';
        $item1 = $this->getLayoutItem('root', $themeName1);
        $item2 = $this->getLayoutItem('custom_root', $themeName2);

        $this->pathProvider->expects(self::exactly(2))
            ->method('setContext')
            ->withConsecutive([$item1->getContext()], [$item2->getContext()]);

        $this->pathProvider->expects(self::exactly(2))
            ->method('getPaths')
            ->willReturnOnConsecutiveCalls([$themeName1], [$themeName2]);

        $this->resourceProvider->expects(self::any())
            ->method('findApplicableResources')
            ->willReturnMap([
                [
                    [$themeName1],
                    ['oro-default/resource1.yml'],
                ],
                [
                    [$themeName2],
                    [
                        'oro-default/resource1.yml',
                        'oro-default/page/resource3.php',
                    ],
                ],
            ]);

        $updateMock = $this->createMock(LayoutUpdateInterface::class);
        $update2Mock = $this->createMock(LayoutUpdateInterface::class);

        $this->yamlDriver->expects(self::exactly(2))
            ->method('load')
            ->with('oro-default/resource1.yml')
            ->willReturn($updateMock);
        $this->phpDriver->expects(self::once())
            ->method('load')
            ->with('oro-default/page/resource3.php')
            ->willReturn($update2Mock);

        $result = $this->extension->getLayoutUpdates($item1);
        self::assertContains($updateMock, $result);
        self::assertNotContains($update2Mock, $result);

        $result = $this->extension->getLayoutUpdates($item2);
        self::assertContains($updateMock, $result);
        self::assertContains($update2Mock, $result);

        // Checks local cache.
        $result = $this->extension->getLayoutUpdates($item1);
        self::assertContains($updateMock, $result);
        self::assertNotContains($update2Mock, $result);

        $result = $this->extension->getLayoutUpdates($item2);
        self::assertContains($updateMock, $result);
        self::assertContains($update2Mock, $result);
    }

    public function testShouldPassDependenciesToUpdateInstance(): void
    {
        $themeName = 'oro-gold';
        $update = $this->createMock(LayoutUpdateInterface::class);
        $this->pathProvider->expects(self::once())
            ->method('getPaths')
            ->willReturn([$themeName]);

        $this->resourceProvider->expects(self::any())
            ->method('findApplicableResources')
            ->with([$themeName])
            ->willReturn([
                'oro-default/resource1.yml',
            ]);

        $this->yamlDriver->expects(self::once())
            ->method('load')
            ->willReturn($update);

        $this->dependencyInitializer->expects(self::once())
            ->method('initialize')
            ->with(self::identicalTo($update));

        $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
    }

    public function testShouldPassContextInContextAwareProvider(): void
    {
        $themeName = 'my-theme';
        $this->pathProvider->expects(self::once())
            ->method('getPaths')
            ->willReturn([$themeName]);

        $this->resourceProvider->expects(self::any())
            ->method('findApplicableResources')
            ->with([$themeName])
            ->willReturn([
                'oro-default/resource1.yml',
                'oro-default/page/resource2.yml',
                'oro-default/page/resource3.php',
            ]);

        $this->pathProvider->expects(self::once())
            ->method('setContext');

        $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
    }

    private function getLayoutItem(string $id, string $theme = null): LayoutItemInterface
    {
        $context = new LayoutContext([], ['theme']);
        $context->set('theme', $theme);
        $context->resolve();
        $layoutItem = (new LayoutItem(new RawLayoutBuilder(), $context));
        $layoutItem->initialize($id);

        return $layoutItem;
    }
}
