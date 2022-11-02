<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\DependencyInjection\Container;

class ConnectorContextMediatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConnectorContextMediator */
    private $contextMediator;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repo;

    /** @var TypesRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    protected function setUp(): void
    {
        $proxiedServiceID = 'registry';

        $this->registry = $this->createMock(TypesRegistry::class);
        $container = new Container();
        $container->set($proxiedServiceID, $this->registry);

        $this->repo = $this->createMock(ChannelRepository::class);

        $em = $this->createMock(EntityManager::class);

        $em->expects($this->any())
            ->method('getRepository')
            ->with('OroIntegrationBundle:Channel')
            ->willReturn($this->repo);
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManager')
            ->willReturn($em);
        $link = new ServiceLink($container, $proxiedServiceID);

        $this->contextMediator = new ConnectorContextMediator($link, $registry);
    }

    /**
     * @dataProvider transportSourceProvider
     */
    public function testGetTransportFromSource(mixed $source, mixed $exceptionExpected)
    {
        if (false !== $exceptionExpected) {
            $this->expectException($exceptionExpected);
        } else {
            $this->registry->expects($this->once())
                ->method('getTransportTypeBySettingEntity')
                ->willReturn(new \stdClass());
        }

        $this->contextMediator->getTransport($source);
    }

    public function transportSourceProvider(): array
    {
        $integration = new Integration();
        $integration->setTransport($this->getMockForAbstractClass(Transport::class));

        return [
            'bad source exception expected' => [false, \LogicException::class],
            'channel given'                 => [$integration, false]
        ];
    }

    public function testGetTransportFromContext()
    {
        $testID = 1;
        $testTransport = new \stdClass();
        $integration = new Integration();
        $integration->setTransport($this->getMockForAbstractClass(Transport::class));

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('getOption')
            ->with('channel')
            ->willReturn($testID);

        $this->repo->expects($this->once())
            ->method('getOrLoadById')
            ->with($testID)
            ->willReturn($integration);

        $this->registry->expects($this->once())
            ->method('getTransportTypeBySettingEntity')
            ->willReturn($testTransport);

        $result = $this->contextMediator->getTransport($context);
        $this->assertEquals($testTransport, $result);
    }

    public function testGetChannelFromContext()
    {
        $testID = 1;
        $integration = new Integration();
        $integration->setTransport($this->getMockForAbstractClass(Transport::class));

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('getOption')
            ->with('channel')
            ->willReturn($testID);

        $this->repo->expects($this->once())
            ->method('getOrLoadById')
            ->with($testID)
            ->willReturn($integration);

        $result = $this->contextMediator->getChannel($context);
        $this->assertEquals($integration, $result);
    }

    public function testGetInitializedTransport()
    {
        $testTransport = $this->createMock(TransportInterface::class);
        $transportEntity = $this->getMockForAbstractClass(Transport::class);
        $integration = new Integration();
        $integration->setTransport($transportEntity);

        $this->registry->expects($this->once())
            ->method('getTransportTypeBySettingEntity')
            ->willReturn($testTransport);

        $testTransport->expects($this->once())
            ->method('init')
            ->with($transportEntity);

        $result = $this->contextMediator->getInitializedTransport($integration);
        $this->assertEquals($testTransport, $result);

        // test local cache
        $this->contextMediator->getInitializedTransport($integration);
    }
}
