<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\DependencyInjection\Container;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

class ConnectorContextMediatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConnectorContextMediator */
    protected $contextMediator;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repo;

    /** @var TypesRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    protected function setUp()
    {
        $proxiedServiceID = 'registry';

        $this->registry = $this->getMock('Oro\Bundle\IntegrationBundle\Manager\TypesRegistry');
        $container      = new Container();
        $container->set($proxiedServiceID, $this->registry);

        $this->repo = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository')
            ->disableOriginalConstructor()->getMock();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();

        $em->expects($this->any())->method('getRepository')->with('OroIntegrationBundle:Channel')
            ->will($this->returnValue($this->repo));
        $registry = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');
        $registry->expects($this->any())->method('getManager')
            ->will($this->returnValue($em));
        $link =new ServiceLink($container, $proxiedServiceID);

        $this->contextMediator = new ConnectorContextMediator($link, $registry);
    }

    protected function tearDown()
    {
        unset($this->repo, $this->registry, $this->contextMediator);
    }

    /**
     * @dataProvider transportSourceProvider
     *
     * @param  mixed $source
     * @param bool   $exceptionExpected
     */
    public function testGetTransportFromSource($source, $exceptionExpected = false)
    {
        if (false !== $exceptionExpected) {
            $this->setExpectedException($exceptionExpected);
        } else {
            $this->registry->expects($this->once())->method('getTransportTypeBySettingEntity')
                ->will($this->returnValue(new \stdClass()));
        }

        $this->contextMediator->getTransport($source);
    }

    /**
     * @return array
     */
    public function transportSourceProvider()
    {
        $integration = new Integration();
        $integration->setTransport($this->getMockForAbstractClass('Oro\\Bundle\\IntegrationBundle\\Entity\\Transport'));
        return [
            'bad source exception expected' => [false, '\LogicException'],
            'channel given'                 => [$integration]
        ];
    }

    public function testGetTransportFromContext()
    {
        $testID        = 1;
        $testTransport = new \stdClass();
        $integration   = new Integration();
        $integration->setTransport($this->getMockForAbstractClass('Oro\\Bundle\\IntegrationBundle\\Entity\\Transport'));

        $context = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $context->expects($this->once())->method('getOption')->with('channel')
            ->will($this->returnValue($testID));

        $this->repo->expects($this->once())->method('getOrLoadById')->with($testID)
            ->will($this->returnValue($integration));

        $this->registry->expects($this->once())->method('getTransportTypeBySettingEntity')
            ->will($this->returnValue($testTransport));

        $result = $this->contextMediator->getTransport($context);
        $this->assertEquals($testTransport, $result);
    }

    public function testGetChannelFromContext()
    {
        $testID      = 1;
        $integration = new Integration();
        $integration->setTransport($this->getMockForAbstractClass('Oro\\Bundle\\IntegrationBundle\\Entity\\Transport'));

        $context = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $context->expects($this->once())->method('getOption')->with('channel')
            ->will($this->returnValue($testID));

        $this->repo->expects($this->once())->method('getOrLoadById')->with($testID)
            ->will($this->returnValue($integration));

        $result = $this->contextMediator->getChannel($context);
        $this->assertEquals($integration, $result);
    }

    public function testGetInitializedTransport()
    {
        $testTransport = $this->getMock('Oro\Bundle\IntegrationBundle\Provider\TransportInterface');
        $transportEntity = $this->getMockForAbstractClass('Oro\Bundle\IntegrationBundle\Entity\Transport');
        $integration = new Integration();
        $integration->setTransport($transportEntity);

        $this->registry->expects($this->once())->method('getTransportTypeBySettingEntity')
            ->will($this->returnValue($testTransport));

        $testTransport->expects($this->once())->method('init')->with($transportEntity);

        $result = $this->contextMediator->getInitializedTransport($integration);
        $this->assertEquals($testTransport, $result);

        // test local cache
        $this->contextMediator->getInitializedTransport($integration);
    }
}
