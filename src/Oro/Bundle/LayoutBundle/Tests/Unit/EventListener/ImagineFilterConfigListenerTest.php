<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use Oro\Bundle\LayoutBundle\EventListener\ImagineFilterConfigListener;
use Oro\Bundle\LayoutBundle\Loader\ImageFilterLoader;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class ImagineFilterConfigListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $listener;

    /** @var ImageFilterLoader */
    protected $loader;

    public function setUp()
    {
        $this->loader = $this->createMock(ImageFilterLoader::class);

        $container = TestContainerBuilder::create()
            ->add('oro_layout.loader.image_filter', $this->loader)
            ->getContainer($this);

        $this->listener = new ImagineFilterConfigListener($container);
    }

    public function testOnKernelRequest()
    {
        $event = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects(self::once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $this->loader->expects(self::once())
            ->method('load');

        $this->listener->onKernelRequest($event);
    }

    public function testOnKernelRequestOnSubRequest()
    {
        $event = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects(self::once())
            ->method('isMasterRequest')
            ->willReturn(false);

        $this->loader->expects(self::never())
            ->method('load');

        $this->listener->onKernelRequest($event);
    }
}
