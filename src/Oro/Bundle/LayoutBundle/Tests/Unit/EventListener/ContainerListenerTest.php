<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\LayoutBundle\EventListener\ContainerListener;
use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\ResourceProviderInterface;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class ContainerListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigMetadataDumperInterface */
    private $dumper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ResourceProviderInterface */
    private $resourceProvider;

    /** @var ContainerListener */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->dumper = $this->createMock(ConfigMetadataDumperInterface::class);
        $this->resourceProvider = $this->createMock(ResourceProviderInterface::class);

        $container = TestContainerBuilder::create()
            ->add('oro_layout.theme_extension.resource_provider.theme', $this->resourceProvider)
            ->getContainer($this);

        $this->listener = new ContainerListener($this->dumper, $container);
    }

    /**
     * @param bool $isMasterRequest
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|GetResponseEvent
     */
    private function getEvent($isMasterRequest = true)
    {
        $event = $this->createMock(GetResponseEvent::class);
        $event->expects(self::any())
            ->method('isMasterRequest')
            ->willReturn($isMasterRequest);

        return $event;
    }

    public function testOnKernelRequestIsNotFresh()
    {
        $this->dumper->expects(self::once())
            ->method('isFresh')
            ->willReturn(false);

        $container = new ContainerBuilder();
        $this->resourceProvider->expects(self::once())
            ->method('loadResources')
            ->with($container);
        $this->dumper->expects(self::once())
            ->method('dump')
            ->with($container);

        $this->listener->onKernelRequest($this->getEvent());
    }

    public function testOnKernelRequestIsFresh()
    {
        $this->dumper->expects(self::once())
            ->method('isFresh')
            ->willReturn(true);

        $this->resourceProvider->expects(self::never())
            ->method('loadResources');
        $this->dumper->expects(self::never())
            ->method('dump');

        $this->listener->onKernelRequest($this->getEvent());
    }

    public function testOnKernelRequestForSubRequest()
    {
        $this->dumper->expects(self::never())
            ->method('isFresh');
        $this->resourceProvider->expects(self::never())
            ->method('loadResources');
        $this->dumper->expects(self::never())
            ->method('dump');

        $this->listener->onKernelRequest($this->getEvent(false));
    }
}
