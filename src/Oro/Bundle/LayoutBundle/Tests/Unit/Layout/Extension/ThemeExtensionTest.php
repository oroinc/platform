<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Tests\Logger;

use Oro\Component\Layout\LayoutContext;
use Oro\Bundle\LayoutBundle\Layout\Loader\ChainLoader;
use Oro\Bundle\LayoutBundle\Layout\Loader\ResourceMatcher;
use Oro\Bundle\LayoutBundle\Layout\Loader\ResourceFactory;
use Oro\Bundle\LayoutBundle\Layout\Loader\LoaderInterface;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeExtension;
use Oro\Bundle\LayoutBundle\Layout\Extension\DependencyInitializer;

class ThemeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ThemeExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ResourceMatcher */
    protected $matcher;

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
        $this->matcher = $this->getMockBuilder('Oro\Bundle\LayoutBundle\Layout\Loader\ResourceMatcher')
            ->disableOriginalConstructor()->getMock();

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
            $this->matcher
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
        $this->assertCount(1, $logs);
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

    public function testConfigureContextWithOutRequest()
    {
        $context = new LayoutContext();

        $this->extension->configureContext($context);

        $context->resolve();
        $this->assertNull($context->get(ThemeExtension::PARAM_THEME));
    }

    public function testConfigureContextWithRequest()
    {
        $context = new LayoutContext();

        $request = Request::create('');
        $request->attributes->set('_theme', 'testTheme');

        $this->extension->setRequest($request);
        $this->extension->configureContext($context);

        $context->resolve();
        $this->assertSame('testTheme', $context->get(ThemeExtension::PARAM_THEME));
    }

    public function testConfigureContextWithThemeInQueryString()
    {
        $context = new LayoutContext();

        $request = Request::create('');
        $request->query->set('_theme', 'testTheme');

        $this->extension->setRequest($request);
        $this->extension->configureContext($context);

        $context->resolve();
        $this->assertSame('testTheme', $context->get(ThemeExtension::PARAM_THEME));
    }

    public function testConfigureContextWithRequestAndDataSetInContext()
    {
        $context = new LayoutContext();
        $context->set(ThemeExtension::PARAM_THEME, 'themeShouldNotBeOverridden');

        $request = Request::create('');
        $request->attributes->set('_theme', 'testTheme');

        $this->extension->setRequest($request);
        $this->extension->configureContext($context);

        $context->resolve();
        $this->assertSame('themeShouldNotBeOverridden', $context->get(ThemeExtension::PARAM_THEME));
    }

    public function testRequestSetterSynchronized()
    {
        $this->extension->setRequest(new Request());
        $this->extension->setRequest(null);
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
        $this->matcher->expects($this->any())->method('match')
            ->willReturn(
                function ($path) use ($themeName) {

                }
            );
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
            ->with($theme)->willReturn($theme);

        return $layoutItem;
    }
}
