<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Symfony\Component\HttpKernel\Tests\Logger;

use Oro\Bundle\LayoutBundle\Layout\Loader\ChainLoader;
use Oro\Bundle\LayoutBundle\Layout\Loader\ChainPathProvider;
use Oro\Bundle\LayoutBundle\Layout\Loader\ResourceFactory;
use Oro\Bundle\LayoutBundle\Layout\Loader\LoaderInterface;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeExtension;
use Oro\Bundle\LayoutBundle\Layout\Extension\DependencyInitializer;

class ThemeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ThemeExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ChainPathProvider */
    protected $provider;

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
            'resource-gold.yml',
            'index' => [
                'resource-update.yml'
            ]
        ],
    ];

    protected function setUp()
    {
        $this->provider   = $this->getMock('\Oro\Bundle\LayoutBundle\Tests\Unit\Stubs\StubContextAwarePathProvider');
        $this->yamlLoader = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Loader\LoaderInterface');
        $this->phpLoader  = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Loader\LoaderInterface');

        $this->logger = new Logger();

        $this->dependencyInitializer = $this
            ->getMockBuilder('\Oro\Bundle\LayoutBundle\Layout\Extension\DependencyInitializer')
            ->disableOriginalConstructor()->getMock();

        $this->extension = new ThemeExtension(
            $this->resources,
            new ResourceFactory(),
            new ChainLoader([$this->yamlLoader, $this->phpLoader]),
            $this->dependencyInitializer,
            $this->provider
        );
        $this->extension->setLogger($this->logger);
    }

    protected function tearDown()
    {
        unset(
            $this->extension,
            $this->matcher,
            $this->yamlLoader,
            $this->phpLoader,
            $this->logger,
            $this->dependencyInitializer
        );
    }

    public function testThemeWithoutUpdatesTheme()
    {
        $themeName = 'my-theme';
        $this->setUpResourcePathProvider($themeName);

        $this->yamlLoader->expects($this->never())->method('supports');
        $this->phpLoader->expects($this->never())->method('supports');

        $this->extension->getLayoutUpdates($this->getLayoutItem('root', $themeName));
    }

    public function testThemeYamlUpdateFound()
    {
        $themeName = 'oro-gold';
        $this->setUpResourcePathProvider($themeName);

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

    public function testUpdatesFoundBasedOnMultiplePaths()
    {
        $themeName = 'oro-gold';
        $this->setUpResourcePathProvider([$themeName], [$themeName.'_index']);

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
        $this->setUpResourcePathProvider($themeName);

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
        $this->assertCount(1, $logs);
        $this->assertSame('Skipping resource "resource2.xml" because loader for it not found', reset($logs));
    }

    public function testShouldPassDependenciesToUpdateInstance()
    {
        $themeName = 'oro-gold';
        $update    = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');
        $this->setUpResourcePathProvider($themeName);

        $callbackBuilder = $this->getCallbackBuilder();
        $this->yamlLoader->expects($this->any())->method('supports')->willReturnCallback($callbackBuilder('yml'));
        $this->yamlLoader->expects($this->once())->method('load')->willReturn($update);

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
            return function ($resource) use ($extension) {
                return substr($resource, -strlen($extension)) === $extension;
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
