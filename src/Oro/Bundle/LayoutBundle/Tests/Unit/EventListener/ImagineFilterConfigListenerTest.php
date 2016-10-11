<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use Oro\Bundle\LayoutBundle\EventListener\ImagineFilterConfigListener;
use Oro\Bundle\LayoutBundle\Loader\ImageFilterLoader;

class ImagineFilterConfigListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ImagineFilterConfigListener
     */
    protected $listener;

    /**
     * @var ImageFilterLoader
     */
    protected $loader;

    public function setUp()
    {
        $this->loader = $this->prophesize(ImageFilterLoader::class);
        $this->listener = new ImagineFilterConfigListener($this->loader->reveal());
    }

    public function testOnKernelRequest()
    {
        $event = $this->prophesize(GetResponseEvent::class);
        $event->isMasterRequest()->willReturn(true);

        $this->loader->load()->shouldBeCalled();

        $this->listener->onKernelRequest($event->reveal());
    }

    public function testOnKernelRequestOnSubRequest()
    {
        $event = $this->prophesize(GetResponseEvent::class);
        $event->isMasterRequest()->willReturn(false);

        $this->loader->load()->shouldNotBeCalled();

        $this->listener->onKernelRequest($event->reveal());
    }
}
