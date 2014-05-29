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
        $securityContext = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $token           = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $this->user      = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');

        $token->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($this->user));
        $securityContext->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        $this->subscriber = new DefaultUserOwnerSubscriber($securityContext);
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
        $channel = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $channel->expects($this->any())->method('getId')
            ->will($this->returnValue(123));

        return [
            'should set if null value given'     => [null, true],
            'should not set for saved  channels' => [$channel, false]
        ];
    }
}
