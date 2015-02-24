<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Symfony\Component\HttpKernel\Tests\Logger;

use Oro\Bundle\LayoutBundle\Theme\ThemeManager;
use Oro\Bundle\LayoutBundle\Layout\Loader\ChainLoader;
use Oro\Bundle\LayoutBundle\Layout\Loader\FileResource;
use Oro\Bundle\LayoutBundle\Layout\Loader\ResourceFactory;
use Oro\Bundle\LayoutBundle\Layout\Loader\LoaderInterface;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeExtension;

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

        $this->extension = new ThemeExtension(
            $this->resources,
            $this->themeManager,
            new ResourceFactory(),
            new ChainLoader([$this->yamlLoader, $this->phpLoader])
        );
        $this->extension->setLogger($this->logger);
    }

    protected function tearDown()
    {
        unset($this->extension, $this->themeManager, $this->yamlLoader, $this->phpLoader, $this->logger);
    }

    public function testThemeWithoutUpdatesTheme()
    {
        $this->setUpActiveTheme('empty-dir');

        $this->yamlLoader->expects($this->never())->method('supports');
        $this->phpLoader->expects($this->never())->method('supports');

        $this->extension->getLayoutUpdates('root');
    }

    public function testThemeYamlUpdateFound()
    {
        $this->setUpActiveTheme('oro-gold');

        $callbackBuilder = $this->getCallbackBuilder();

        $this->yamlLoader->expects($this->any())->method('supports')
            ->willReturnCallback($callbackBuilder('yml'));
        $this->phpLoader->expects($this->never())->method('supports')
            ->willReturnCallback($callbackBuilder('php'));

        $updateMock = $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');

        $this->yamlLoader->expects($this->once())->method('load')->with('resource-gold.yml')
            ->willReturn($updateMock);

        $result = $this->extension->getLayoutUpdates('root');
        $this->assertContains($updateMock, $result);
    }

    public function testThemeUpdatesFoundWithOneSkipped()
    {
        $this->setUpActiveTheme('oro-default');

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

        $result = $this->extension->getLayoutUpdates('root');
        $this->assertContains($updateMock, $result);
        $this->assertContains($update2Mock, $result);

        $logs = $this->logger->getLogs('notice');
        $this->assertSame('Skipping resource "resource2.xml" because loader for it not found', reset($logs));
    }

    public function testShouldCreateRouteFileResourceForNestingFiles()
    {
        $this->setUpActiveTheme('oro-black');

        $callbackBuilder = $this->getCallbackBuilder();

        $this->yamlLoader->expects($this->any())->method('supports')
            ->willReturnCallback($callbackBuilder('yml'));

        $this->yamlLoader->expects($this->once())->method('load')
            ->willReturnCallback(
                function (FileResource $resource) {
                    $this->assertNotEmpty($resource->getConditions());
                    $this->assertContainsOnlyInstancesOf(
                        'Oro\Bundle\LayoutBundle\Layout\Generator\Condition\SimpleContextValueComparisonCondition',
                        $resource->getConditions()
                    );

                    return $this->getMock('Oro\Component\Layout\LayoutUpdateInterface');
                }
            );

        $this->extension->getLayoutUpdates('root');
    }

    public function testShouldPassDependenciesToUpdateInstance()
    {
        
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
     * @param string $dir
     */
    protected function setUpActiveTheme($dir)
    {
        $themeMock = $this->getMock('Oro\Bundle\LayoutBundle\Model\Theme', [], [], '', false);

        $this->themeManager->expects($this->once())->method('getTheme')->with($this->isNull())
            ->willReturn($themeMock);

        $themeMock->expects($this->any())->method('getDirectory')
            ->willReturn($dir);
    }
}
