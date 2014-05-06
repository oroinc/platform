<?php
namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\IntegrationBundle\Form\EventListener\ChannelFormTwoWaySyncSubscriber;
use Symfony\Component\Form\FormEvents;

class ChannelFormTwoWaySyncSubscriberTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Doctrine\Common\Persistence\ObjectManager */
    protected $om;

    /** @var \Symfony\Component\Form\FormFactoryInterface */
    protected $formBuilder;

    /**
     * @var ChannelFormTwoWaySyncSubscriber
     */
    protected $subscriber;

    /**
     * SetUp test environment
     */
    public function setUp()
    {
        $this->om = $this->getMock('Oro\Bundle\IntegrationBundle\Manager\TypesRegistry');
        $this->formBuilder = $this->getMock('Symfony\Component\Form\FormFactoryInterface');

        $this->subscriber = new ChannelFormTwoWaySyncSubscriber($this->om, $this->formBuilder);
    }

    public function testGetSubscribedEvents()
    {
        $result = $this->subscriber->getSubscribedEvents();

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $result);
    }


    public function testPreSetDataHasRegion()
    {
        $eventMock = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
/*
        $configMock = $this->getMock('Symfony\Component\Form\FormConfigInterface');
        $configMock->expects($this->once())
            ->method('getOptions')
            ->will($this->returnValue(array()));

        $fieldMock = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $formMock = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $formMock->expects($this->once())
            ->method('has')
            ->with($this->equalTo('region'))
            ->will($this->returnValue(true));

        $formMock->expects($this->once())
            ->method('get')
            ->with($this->equalTo('region'))
            ->will($this->returnValue($fieldMock));

        $formMock->expects($this->once())
            ->method('add');

        $fieldMock->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($configMock));

        $newFieldMock = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();


        $this->formBuilder->expects($this->once())
            ->method('createNamed')
            ->will($this->returnValue($newFieldMock));

        $eventMock->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($addressMock));
        $eventMock->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($formMock));
*/
        $this->assertNull($this->subscriber->preSet($eventMock));
    }
}
