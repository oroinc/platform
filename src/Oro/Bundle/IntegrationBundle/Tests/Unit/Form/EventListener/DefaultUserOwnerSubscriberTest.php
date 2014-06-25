<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\IntegrationBundle\Form\EventListener\DefaultUserOwnerSubscriber;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class DefaultUserOwnerSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var */
    protected $user;

    /** @var DefaultUserOwnerSubscriber */
    protected $subscriber;

    public function setUp()
    {
        $this->user     = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');
        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()->getMock();

        $securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->will($this->returnValue($this->user));

        $this->subscriber = new DefaultUserOwnerSubscriber($securityFacade);
    }

    public function tearDown()
    {
        unset($this->subscriber);
    }

    /**
     * @dataProvider formDataProvider
     *
     * @param mixed $formData
     * @param bool  $expectedCall
     */
    public function testPostSet($formData, $expectedCall)
    {
        $events = $this->subscriber->getSubscribedEvents();
        $this->assertArrayHasKey(FormEvents::POST_SET_DATA, $events);
        $this->assertEquals($events[FormEvents::POST_SET_DATA], 'postSet');

        $form      = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $fieldMock = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $form->expects($this->any())->method('get')->with($this->equalTo('defaultUserOwner'))
            ->will($this->returnValue($fieldMock));
        if ($expectedCall) {
            $fieldMock->expects($this->once())->method('setData')->with($this->identicalTo($this->user));
        } else {
            $fieldMock->expects($this->never())->method('setData');
        }

        $event = new  FormEvent($form, $formData);
        $this->subscriber->postSet($event);
    }

    /**
     * @return array
     */
    public function formDataProvider()
    {
        $integration = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $integration->expects($this->any())->method('getId')
            ->will($this->returnValue(123));

        return [
            'should set if null value given'        => [null, true],
            'should not set for saved integrations' => [$integration, false]
        ];
    }
}
