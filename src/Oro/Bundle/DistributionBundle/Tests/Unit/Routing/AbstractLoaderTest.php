<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Routing;

use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelInterface;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;
use Oro\Bundle\DistributionBundle\Routing\AbstractLoader;

abstract class AbstractLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FileLocatorInterface
     */
    protected $locator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|KernelInterface
     */
    protected $kernel;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    protected $eventDispatcher;

    protected function setUp()
    {
        $this->locator = $this->getMock('Symfony\Component\Config\FileLocatorInterface');
        $this->kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    }

    protected function tearDown()
    {
        unset($this->locator, $this->kernel, $this->eventDispatcher);
    }

    public function testSupportsFailed()
    {
        $this->assertFalse($this->getLoader()->supports(null, 'not_supported'));
    }

    /**
     * @param array $expected
     *
     * @dataProvider loadDataProvider
     */
    public function testLoad(array $expected)
    {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR;
        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $bundle->expects($this->any())->method('getPath')->willReturn($dir);

        $this->locator->expects($this->atLeastOnce())->method('locate')->willReturnOnConsecutiveCalls(
            $this->returnCallback(
                function ($filename) use ($dir) {
                    return $dir . $filename;
                }
            ),
            $this->returnArgument(0),
            $this->throwException(new \InvalidArgumentException())
        );

        $this->kernel->expects($this->once())->method('getBundles')->willReturn([$bundle, $bundle]);

        $this->eventDispatcher->expects($this->once())->method('dispatch')->with(
            $this->isType('string'),
            $this->callback(
                function (RouteCollectionEvent $event) use ($expected) {
                    $this->assertEquals($expected, $event->getCollection()->getIterator()->getArrayCopy());

                    return true;
                }
            )
        );

        $this->assertEquals($expected, $this->getLoader()->load('file', 'type')->getIterator()->getArrayCopy());
    }

    public function testDispatchEventWithoutEventDispatcher()
    {
        $this->kernel->expects($this->once())->method('getBundles')->willReturn([]);
        $this->eventDispatcher->expects($this->never())->method('dispatch');
        $this->assertEquals(
            [],
            $this->getLoaderWithoutEventDispatcher()->load('file', 'type')->getIterator()->getArrayCopy()
        );
    }

    /**
     * @return AbstractLoader
     */
    abstract public function getLoader();

    /**
     * @return AbstractLoader
     */
    abstract public function getLoaderWithoutEventDispatcher();

    /**
     * @return array
     */
    abstract public function loadDataProvider();
}
