<?php
namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;
use Oro\Bundle\IntegrationBundle\Form\EventListener\ChannelFormTwoWaySyncSubscriber;

class ChannelFormTwoWaySyncSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var TypesRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $typesRegistry;

    /** @var ChannelFormTwoWaySyncSubscriber */
    protected $subscriber;

    /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventMock;

    /** @var TwoWaySyncConnectorInterface */
    protected $connector;

    /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formMock;

    /** @var Channel|\PHPUnit_Framework_MockObject_MockObject */
    protected $data;

    public function setUp()
    {
        $this->typesRegistry = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Manager\TypesRegistry')
            ->disableOriginalConstructor()->getMock();
        $this->eventMock     = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()->getMock();
        $this->formMock      = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $this->data          = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $this->connector     = $this->getMock('Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface');

        $this->subscriber = new ChannelFormTwoWaySyncSubscriber($this->typesRegistry);
    }

    public function tearDown()
    {
        unset(
            $this->typesRegistry,
            $this->eventMock,
            $this->connector,
            $this->formMock,
            $this->data,
            $this->subscriber
        );
    }

    public function testGetSubscribedEvents()
    {
        $result = $this->subscriber->getSubscribedEvents();

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $result);
    }

    public function testPreSetWithoutTwoWay()
    {
        $this->data->expects($this->at(0))
            ->method('getType')
            ->will($this->returnValue('test'));

        $this->typesRegistry->expects($this->once())
            ->method('getRegisteredConnectorsTypes')
            ->will($this->returnValue([]));

        $this->formMock->expects($this->never())
            ->method('add')
            ->will($this->returnValue($this->formMock));

        $this->eventMock->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($this->formMock));
        $this->eventMock->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($this->data));

        $this->subscriber->preSet($this->eventMock);
    }

    public function testPreSetWithTwoWay()
    {
        $this->data->expects($this->at(0))
            ->method('getType')
            ->will($this->returnValue('test'));

        $this->typesRegistry->expects($this->exactly(2))
            ->method('getRegisteredConnectorsTypes')
            ->will($this->returnValue([$this->connector]));

        $this->formMock->expects($this->once())
            ->method('add')
            ->will($this->returnValue($this->formMock));

        $this->eventMock->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($this->formMock));
        $this->eventMock->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($this->data));

        $this->subscriber->preSet($this->eventMock);
    }
}
