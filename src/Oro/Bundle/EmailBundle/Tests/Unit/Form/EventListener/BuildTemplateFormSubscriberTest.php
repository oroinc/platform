<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\EventListener;

use Symfony\Component\Form\FormEvents;

use Oro\Bundle\EmailBundle\Form\EventListener\BuildTemplateFormSubscriber;

class BuildTemplateFormSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var BuildTemplateFormSubscriber */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityContext;

    /**
     * SetUp test environment
     */
    protected function setUp()
    {
        $this->securityContext  = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $this->listener = new BuildTemplateFormSubscriber($this->securityContext);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->securityContext);
        unset($this->listener);
    }

    public function testGetSubscribedEvents()
    {
        $result = $this->listener->getSubscribedEvents();

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $result);
        $this->assertArrayHasKey(FormEvents::PRE_SUBMIT, $result);
    }

    public function testPreSetDataEmptyData()
    {
        $eventMock = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $eventMock->expects($this->once())
            ->method('getData')
            ->will($this->returnValue(null));
        $eventMock->expects($this->never())
            ->method('getForm');

        $this->listener->preSetData($eventMock);
    }

    public function testPreSetDataEmptyEntityName()
    {
        $eventMock = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $notificationMock = $this->getMock('Oro\Bundle\NotificationBundle\Entity\EmailNotification');
        $notificationMock->expects($this->once())
            ->method('getEntityName')
            ->will($this->returnValue(null));

        $eventMock->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($notificationMock));

        $this->listener->preSetData($eventMock);
    }

    public function testPreSetDataHasTemplates()
    {
        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\Organization');
        $token = $this->getMockBuilder(
            'Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($organization));

        $eventMock = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $notificationMock = $this->getMock('Oro\Bundle\NotificationBundle\Entity\EmailNotification');
        $notificationMock->expects($this->exactly(2))
            ->method('getEntityName')
            ->will($this->returnValue('testEntity'));

        $configMock = $this->getMock('Symfony\Component\Form\FormConfigInterface');
        $configMock->expects($this->once())
            ->method('getOptions')
            ->will($this->returnValue(array('auto_initialize' => true)));

        $formType   = $this->getMock('Symfony\Component\Form\ResolvedFormTypeInterface');
        $formType->expects($this->once())->method('getName')
            ->will($this->returnValue('template'));
        $configMock->expects($this->once())->method('getType')
            ->will($this->returnValue($formType));

        $fieldMock = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $formMock = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $formMock->expects($this->once())
            ->method('get')
            ->with($this->equalTo('template'))
            ->will($this->returnValue($fieldMock));
        $formMock->expects($this->once())
            ->method('add');

        $fieldMock->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($configMock));

        $eventMock->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($notificationMock));
        $eventMock->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($formMock));

        $this->listener->preSetData($eventMock);
    }

    public function testPreSubmitData()
    {
        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\Organization');
        $token = $this->getMockBuilder(
            'Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($organization));

        $eventMock = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $configMock = $this->getMock('Symfony\Component\Form\FormConfigInterface');
        $configMock->expects($this->once())->method('getOptions')
            ->will($this->returnValue(array('auto_initialize' => true)));

        $formType   = $this->getMock('Symfony\Component\Form\ResolvedFormTypeInterface');
        $formType->expects($this->once())->method('getName')
            ->will($this->returnValue('template'));
        $configMock->expects($this->once())->method('getType')
            ->will($this->returnValue($formType));

        $fieldMock = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $fieldMock->expects($this->once())->method('getConfig')
            ->will($this->returnValue($configMock));

        $formMock = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $formMock->expects($this->once())
            ->method('get')
            ->with($this->equalTo('template'))
            ->will($this->returnValue($fieldMock));
        $formMock->expects($this->once())
            ->method('add');

        $eventMock->expects($this->once())
            ->method('getData')
            ->will($this->returnValue(array('entityName' => 'testEntityName')));
        $eventMock->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($formMock));

        $this->listener->preSubmit($eventMock);
    }
}
