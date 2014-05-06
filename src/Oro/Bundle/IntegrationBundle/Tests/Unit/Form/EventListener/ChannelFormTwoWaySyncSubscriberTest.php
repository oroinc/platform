<?php
namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\IntegrationBundle\Form\EventListener\ChannelFormTwoWaySyncSubscriber;
use Symfony\Component\Form\FormEvents;

class ChannelFormTwoWaySyncSubscriberTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Oro\Bundle\IntegrationBundle\Manager\TypesRegistry */
    protected $typesRegistry;

    /**
     * @var AddressCountryAndRegionSubscriber
     */
    protected $subscriber;

    /**
     * SetUp test environment
     */
    public function setUp()
    {
        $this->typesRegistry = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Manager\TypesRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->subscriber = new ChannelFormTwoWaySyncSubscriber($this->typesRegistry);
    }

    public function testGetSubscribedEvents()
    {
        $result = $this->subscriber->getSubscribedEvents();

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $result);
    }


    public function testPreSetWithoutTwoWay()
    {
        $data = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $data->expects($this->at(0))
            ->method('getType')
            ->will($this->returnValue('test'));

        $eventMock = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->typesRegistry->expects($this->once())
            ->method('getRegisteredConnectorsTypes')
            ->will($this->returnValue([]));

        $formMock = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $formMock->expects($this->never())
            ->method('add')
            ->will($this->returnValue($formMock));

        $eventMock->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($formMock));

        $eventMock->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));

        $this->assertNull($this->subscriber->preSet($eventMock));
    }

    public function testPreSetWithTwoWay()
    {
        $data = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $data->expects($this->at(0))
            ->method('getType')
            ->will($this->returnValue('test'));

        $eventMock = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $connector = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->typesRegistry->expects($this->once())
            ->method('getRegisteredConnectorsTypes')
            ->will($this->returnValue([$connector]));

        $formMock = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $formMock->expects($this->exactly(2))
            ->method('add')
            ->will($this->returnValue($formMock));

        $eventMock->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($formMock));

        $eventMock->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));

        $this->assertNull($this->subscriber->preSet($eventMock));
    }
}
