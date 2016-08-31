<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\EventListener;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use Oro\Component\Config\Dumper\ConfigMetadataDumperInterface;

use Oro\Bundle\DataGridBundle\EventListener\ContainerListener;

/**
 * Class ContainerListenerTest for ContainerListener
 *
 * It implements ConfigMetadataDumperInterface just to simplify test flow
 * (Self-Shunt test pattern http://www.whiteboxtest.com/Test-Pattern-SelfShunt.php)
 */
class ContainerListenerTest extends \PHPUnit_Framework_TestCase implements ConfigMetadataDumperInterface
{
    /** @var  ContainerListener */
    private $listener;

    private $configProviderStub;

    /** @var GetResponseEvent */
    private $event;

    /** @var  bool */
    private $isFresh;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configProviderStub = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Provider\ConfigurationProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new ContainerListener($this->configProviderStub, $this);
    }

    /**
     * Tests onKernelRequest in case we have data in cache. So we should not regenerate it
     */
    public function testOnKernelRequestWithWarmedCache()
    {
        $this->isFresh = true;

        $this->configProviderStub->expects($this->never())
            ->method('loadConfiguration');

        $this->listener->onKernelRequest($this->event);
    }

    /**
     * Tests onKernelRequest in case we have empty cache
     */
    public function testOnKernelRequestWithoutCahe()
    {
        $this->isFresh = false;

        $this->configProviderStub->expects($this->once())
            ->method('loadConfiguration');

        $this->listener->onKernelRequest($this->event);
    }

    /**
     * {@inheritdoc}
     */
    public function dump(ContainerBuilder $container)
    {
        // Do nothing created just to implement an interface
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh()
    {
        return $this->isFresh;
    }
}
