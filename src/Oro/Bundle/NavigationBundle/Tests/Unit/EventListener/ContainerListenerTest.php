<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\EventListener;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use Oro\Bundle\NavigationBundle\EventListener\ContainerListener;
use Oro\Bundle\NavigationBundle\Provider\ConfigurationProvider;

use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;

class ContainerListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContainerListener */
    protected $listener;

    /** @var ConfigurationProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $configurationProvider;

    /** @var ConfigMetadataDumperInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $dumper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configurationProvider = $this->createMock(ConfigurationProvider::class);
        $this->dumper = $this->createMock(ConfigMetadataDumperInterface::class);

        $this->listener = new ContainerListener($this->configurationProvider, $this->dumper);
    }

    public function testOnKernelRequest()
    {
        $this->dumper
            ->expects($this->once())
            ->method('isFresh')
            ->will($this->returnValue(false));

        $container = new ContainerBuilder();

        $this->configurationProvider
            ->expects($this->once())
            ->method('loadConfiguration')
            ->with($container);

        $this->dumper
            ->expects($this->once())
            ->method('dump')
            ->with($container);

        /** @var GetResponseEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->createMock(GetResponseEvent::class);
        $this->listener->onKernelRequest($event);
    }

    public function testOnKernelRequestIsFresh()
    {
        $this->dumper
            ->expects($this->once())
            ->method('isFresh')
            ->will($this->returnValue(true));

        $this->configurationProvider
            ->expects($this->never())
            ->method('loadConfiguration');

        $this->dumper
            ->expects($this->never())
            ->method('dump');

        /** @var GetResponseEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->createMock(GetResponseEvent::class);
        $this->listener->onKernelRequest($event);
    }
}
