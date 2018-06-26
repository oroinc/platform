<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\LayoutBundle\EventListener\ImagineFilterConfigListener;
use Oro\Bundle\LayoutBundle\Loader\ImageFilterLoader;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class ImagineFilterConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
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
