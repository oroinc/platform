<?php
namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\IntegrationBundle\Form\EventListener\ChannelFormTwoWaySyncSubscriber;
use Symfony\Component\Form\FormEvents;

class ChannelFormTwoWaySyncSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Oro\Bundle\IntegrationBundle\Manager\TypesRegistry */
    protected $typesRegistry;

    /** @var AddressCountryAndRegionSubscriber */
    protected $subscriber;

    /** @var \Symfony\Component\Form\FormEvent */
    protected $eventMock;

    /** @var \Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface */
    protected $connector;

    /** @var \Symfony\Component\Form\Test\FormInterface */
    protected $formMock;

    /** @var \Oro\Bundle\IntegrationBundle\Entity\Channel */
    protected $data;

    /**
     * SetUp test environment
     */
    public function setUp()
    {
        $this->typesRegistry = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Manager\TypesRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->subscriber = new ChannelFormTwoWaySyncSubscriber($this->typesRegistry);

        $this->eventMock = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->connector = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formMock = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->data = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
    }

    public function tearDown()
    {
        unset(
            $this->typesRegistry,
            $this->subscriber,
            $this->eventMock,
            $this->connector,
            $this->formMock,
            $this->data
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

        $this->assertNull($this->subscriber->preSet($this->eventMock));
    }

    public function testPreSetWithTwoWay()
    {
        $this->data->expects($this->at(0))
            ->method('getType')
            ->will($this->returnValue('test'));

        $this->typesRegistry->expects($this->once())
            ->method('getRegisteredConnectorsTypes')
            ->will($this->returnValue([$this->connector]));

        $this->formMock->expects($this->exactly(2))
            ->method('add')
            ->will($this->returnValue($this->formMock));

        $this->eventMock->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($this->formMock));
        $this->eventMock->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($this->data));

        $this->assertNull($this->subscriber->preSet($this->eventMock));
    }
}
