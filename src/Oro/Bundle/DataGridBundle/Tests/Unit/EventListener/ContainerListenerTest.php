<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\EventListener\ContainerListener;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProvider;
use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class ContainerListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigMetadataDumperInterface */
    private $dumper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigurationProvider */
    private $configProvider;

    /** @var ContainerListener */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->dumper = $this->createMock(ConfigMetadataDumperInterface::class);
        $this->configProvider = $this->createMock(ConfigurationProvider::class);

        $container = TestContainerBuilder::create()
            ->add('oro_datagrid.configuration.provider', $this->configProvider)
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
        $this->configProvider->expects(self::once())
            ->method('loadConfiguration')
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

        $this->configProvider->expects(self::never())
            ->method('loadConfiguration');
        $this->dumper->expects(self::never())
            ->method('dump');

        $this->listener->onKernelRequest($this->getEvent());
    }

    public function testOnKernelRequestForSubRequest()
    {
        $this->dumper->expects(self::never())
            ->method('isFresh');
        $this->configProvider->expects(self::never())
            ->method('loadConfiguration');
        $this->dumper->expects(self::never())
            ->method('dump');

        $this->listener->onKernelRequest($this->getEvent(false));
    }
}
