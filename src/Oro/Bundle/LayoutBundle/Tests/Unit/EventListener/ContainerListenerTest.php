<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\EventListener;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\ResourceProviderInterface;

use Oro\Bundle\LayoutBundle\EventListener\ContainerListener;

class ContainerListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContainerListener */
    protected $listener;

    /** @var ResourceProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $provider;

    /** @var ConfigMetadataDumperInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $dumper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->provider = $this->getMock(ResourceProviderInterface::class);
        $this->dumper = $this->getMock(ConfigMetadataDumperInterface::class);

        $this->listener = new ContainerListener($this->provider, $this->dumper);
    }

    public function testOnKernelRequest()
    {
        $this->dumper
            ->expects($this->once())
            ->method('isFresh')
            ->will($this->returnValue(false));

        $container = new ContainerBuilder();

        $this->provider
            ->expects($this->once())
            ->method('loadResources')
            ->with($container);

        $this->dumper
            ->expects($this->once())
            ->method('dump')
            ->with($container);

        /** @var GetResponseEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMock(GetResponseEvent::class, [], [], '', false);
        $this->listener->onKernelRequest($event);
    }
}
