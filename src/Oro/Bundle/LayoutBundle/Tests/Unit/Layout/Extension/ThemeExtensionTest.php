<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Symfony\Component\HttpKernel\Tests\Logger;

use Oro\Component\Layout\LayoutContext;
use Oro\Bundle\LayoutBundle\Theme\ThemeManager;
use Oro\Bundle\LayoutBundle\Layout\Loader\ChainLoader;
use Oro\Bundle\LayoutBundle\Layout\Loader\ResourceFactory;
use Oro\Bundle\LayoutBundle\Layout\Loader\LoaderInterface;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeExtension;
use Oro\Bundle\LayoutBundle\Layout\Extension\DependencyInitializer;

class ThemeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ThemeExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ThemeManager */
    protected $themeManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|LoaderInterface */
    protected $phpLoader;

    /** @var \PHPUnit_Framework_MockObject_MockObject|LoaderInterface */
    protected $yamlLoader;

    /** @var Logger */
    protected $logger;

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
            'resource-gold.yml'
        ],
        'oro-black'   => [
            'route_name' => ['resource1.yml']
        ]
    ];

    protected function setUp()
    {
        $this->themeManager = $this->getMockBuilder('Oro\Bundle\LayoutBundle\Theme\ThemeManager')
            ->disableOriginalConstructor()->getMock();

        $this->yamlLoader = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Loader\LoaderInterface');
        $this->phpLoader  = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Loader\LoaderInterface');

        $this->logger = new Logger();

        $this->dependencyInitializer = $this
            ->getMockBuilder('\Oro\Bundle\LayoutBundle\Layout\Extension\DependencyInitializer')
            ->disableOriginalConstructor()->getMock();

        $this->extension = new ThemeExtension(
            $this->resources,
            $this->themeManager,
            new ResourceFactory(),
            new ChainLoader([$this->yamlLoader, $this->phpLoader]),
            $this->dependencyInitializer
        );
        $this->extension->setLogger($this->logger);
    }

    protected function tearDown()
    {
        unset(
            $this->extension,
            $this->themeManager,
            $this->yamlLoader,
            $this->phpLoader,
            $this->logger,
            $this->dependencyInitializer
        );
    }

    public function testThemeWithoutUpdatesTheme()
    {
        $themeName = 'my-theme';
        $this->setUpActiveTheme($themeName, 'empty-dir');

        $this->yamlLoader->expects($this->never())->method('supports');
        $this->phpLoader->expects($this->never())->method('supports');

        $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
    }

    public function testThemeYamlUpdateFound()
    {
        $themeName = 'oro-gold';
        $this->setUpActiveTheme($themeName);

        $callbackBuilder = $this->getCallbackBuilder();

        $this->yamlLoader->expects($this->any())->method('supports')
            ->willReturnCallback($callbackBuilder('yml'));
        $this->phpLoader->expects($this->never())->method('supports')
            ->willReturnCallback($callbackBuilder('php'));

        $updateMock = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');

        $this->yamlLoader->expects($this->once())->method('load')->with('resource-gold.yml')
            ->willReturn($updateMock);

        $result = $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
        $this->assertContains($updateMock, $result);
    }

    public function testThemeUpdatesFoundWithOneSkipped()
    {
        $themeName = 'oro-default';
        $this->setUpActiveTheme($themeName);

        $callbackBuilder = $this->getCallbackBuilder();

        $this->yamlLoader->expects($this->any())->method('supports')
            ->willReturnCallback($callbackBuilder('yml'));
        $this->phpLoader->expects($this->any())->method('supports')
            ->willReturnCallback($callbackBuilder('php'));

        $updateMock  = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');
        $update2Mock = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');

        $this->yamlLoader->expects($this->once())->method('load')->with('resource1.yml')
            ->willReturn($updateMock);
        $this->phpLoader->expects($this->once())->method('load')->with('resource3.php')
            ->willReturn($update2Mock);

        $result = $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
        $this->assertContains($updateMock, $result);
        $this->assertContains($update2Mock, $result);

        $logs = $this->logger->getLogs('notice');
        $this->assertSame('Skipping resource "resource2.xml" because loader for it not found', reset($logs));
    }

    public function testShouldLoadRouteRelatedUpdatesIfContextConfigured()
    {
        $themeName = 'oro-black';
        $this->setUpActiveTheme($themeName);

        $callbackBuilder = $this->getCallbackBuilder();

        $this->yamlLoader->expects($this->any())->method('supports')
            ->willReturnCallback($callbackBuilder('yml'));

        $updateMock = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');
        $this->yamlLoader->expects($this->once())->method('load')->with('resource1.yml')->willReturn($updateMock);

        $result = $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName, 'route_name'));
        $this->assertContains($updateMock, $result);
    }

    public function testShouldNotLoadRouteRelatedUpdates()
    {
        $themeName = 'oro-black';
        $this->setUpActiveTheme($themeName);

        $this->yamlLoader->expects($this->never())->method('supports');
        $this->yamlLoader->expects($this->never())->method('load');

        $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
    }

    public function testShouldPassDependenciesToUpdateInstance()
    {
        $themeName = 'oro-gold';
        $update    = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');
        $this->setUpActiveTheme($themeName);

        $callbackBuilder = $this->getCallbackBuilder();
        $this->yamlLoader->expects($this->any())->method('supports')->willReturnCallback($callbackBuilder('yml'));
        $this->yamlLoader->expects($this->once())->method('load')->willReturn($update);

        $this->dependencyInitializer->expects($this->once())->method('initialize')->with($this->identicalTo($update));

        $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
    }

    public function testConfigureContextSetOptionalThemeOption()
    {
        $context = new LayoutContext();
        $this->extension->configureContext($context);

        $context->resolve();

        $this->assertNull($context->getOr('theme'));
    }

    public function testConfigureContextThemeIsAKnownOption()
    {
        $context = new LayoutContext();
        $context->set('theme', 'my-oro-theme');
        $this->extension->configureContext($context);

        $context->resolve();

        $this->assertSame('my-oro-theme', $context->get('theme'));
    }

    public function testConfigureContextThemeShouldBeTakenFormManager()
    {
        $context = new LayoutContext();
        $this->extension->configureContext($context);

        $themeName = 'my-oro-theme';
        $this->themeManager->expects($this->once())->method('getActiveTheme')->willReturn($themeName);

        $context->resolve();

        $this->assertSame($themeName, $context->get('theme'));
    }

    protected function getCallbackBuilder()
    {
        return function ($extension) {
            return function ($resource) use ($extension) {
                return substr($resource, -strlen($extension)) === $extension;
            };
        };
    }

    /**
     * @param string $themeName
     * @param string $dir
     */
    protected function setUpActiveTheme($themeName, $dir = null)
    {
        $themeMock = $this->getMock('Oro\Bundle\LayoutBundle\Model\Theme', [], [], '', false);

        $this->themeManager->expects($this->once())->method('getTheme')->with($themeName)->willReturn($themeMock);
        $themeMock->expects($this->any())->method('getDirectory')->willReturn($dir ?: $themeName);
    }

    /**
     * @param string      $id
     * @param null|string $theme
     * @param null|string $route
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getLayoutItem($id, $theme = null, $route = null)
    {
        $layoutItem = $this->getMock('Oro\Component\Layout\LayoutItemInterface');
        $context    = $this->getMock('Oro\Component\Layout\ContextInterface');

        $layoutItem->expects($this->any())->method('getId')->willReturn($id);
        $layoutItem->expects($this->any())->method('getContext')->willReturn($context);

        $context->expects($this->any())->method('getOr')->willReturnMap(
            [
                ['theme', null, $theme],
                ['route_name', null, $route],
            ]
        );

        return $layoutItem;
    }
}
