<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\DependencyInjection\Container;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
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

        $this->contextMediator = new ConnectorContextMediator(new ServiceLink($container, $proxiedServiceID), $em);
    }

    public function tearDown()
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
        $channel = new Channel();
        $channel->setTransport($this->getMockForAbstractClass('Oro\\Bundle\\IntegrationBundle\\Entity\\Transport'));
        return [
            'bad source exception expected' => [false, '\LogicException'],
            'channel given'                 => [$channel]
        ];
    }

    public function testGetTransportFromContext()
    {
        $testID        = 1;
        $testTransport = new \stdClass();
        $channel       = new Channel();
        $channel->setTransport($this->getMockForAbstractClass('Oro\\Bundle\\IntegrationBundle\\Entity\\Transport'));

        $context = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $context->expects($this->once())->method('getOption')->with('channel')
            ->will($this->returnValue($testID));

        $this->repo->expects($this->once())->method('getOrLoadById')->with($testID)
            ->will($this->returnValue($channel));

        $this->registry->expects($this->once())->method('getTransportTypeBySettingEntity')
            ->will($this->returnValue($testTransport));

        $result = $this->contextMediator->getTransport($context);
        $this->assertEquals($testTransport, $result);
    }

    public function testGetChannelFromContext()
    {
        $testID  = 1;
        $channel = new Channel();
        $channel->setTransport($this->getMockForAbstractClass('Oro\\Bundle\\IntegrationBundle\\Entity\\Transport'));

        $context = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $context->expects($this->once())->method('getOption')->with('channel')
            ->will($this->returnValue($testID));

        $this->repo->expects($this->once())->method('getOrLoadById')->with($testID)
            ->will($this->returnValue($channel));

        $result = $this->contextMediator->getChannel($context);
        $this->assertEquals($channel, $result);
    }
}
