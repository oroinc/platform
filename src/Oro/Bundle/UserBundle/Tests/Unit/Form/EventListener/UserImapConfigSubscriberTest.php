<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\EventListener;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\EventListener\UserImapConfigSubscriber;

class UserImapConfigSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityContext;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    /** @var RequestStack */
    protected $requestStack;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $eventMock;

    /** @var  UserImapConfigSubscriber */
    protected $subscriber;

    protected function setUp()
    {
        $this->securityContext = $this->getMockForAbstractClass(
            'Symfony\Component\Security\Core\SecurityContextInterface'
        );

        $this->requestStack = new RequestStack();

        $this->manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventMock = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->subscriber = new UserImapConfigSubscriber($this->manager, $this->requestStack, $this->securityContext);
    }

    public function testSubscribedEvents()
    {
        $this->assertEquals(
            [
                FormEvents::PRE_SET_DATA => 'preSetData',
                FormEvents::POST_SUBMIT => 'postSubmit',
                FormEvents::PRE_SUBMIT => 'preSubmit',
            ],
            $this->subscriber->getSubscribedEvents()
        );
    }

    public function testPostSubmit()
    {
        $user = new User();

        $this->eventMock->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($user));

        $this->manager->expects($this->once())->method('persist')->with($this->equalTo($user));
        $this->manager->expects($this->once())->method('flush');

        $this->subscriber->postSubmit($this->eventMock);
    }

    public function testPreSetDataForUserConfig()
    {
        $id = 1;
        $user = new User();
        $request = new Request();
        $request->attributes->add(
            [
                '_route' => 'oro_user_config',
                'id' => $id
            ]
        );
        $this->requestStack->push($request);

        $this->manager->expects($this->once())->method('find')->with('OroUserBundle:User', $id)
            ->will($this->returnValue($user));

        $this->eventMock->expects($this->once())
            ->method('setData')
            ->with($this->equalTo($user));

        $this->subscriber->preSetData($this->eventMock);
    }

    public function testPreSetDataForProfileConfig()
    {
        $user = new User();
        $request = new Request();
        $request->attributes->add(
            [
                '_route' => 'oro_user_profile_configuration',
            ]
        );
        $this->requestStack->push($request);

        $this->manager->expects($this->never())->method('find');

        $token = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $token->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($user));

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $this->eventMock->expects($this->once())
            ->method('setData')
            ->with($this->equalTo($user));

        $this->subscriber->preSetData($this->eventMock);
    }

    public function testPreSubmit()
    {
        $data = ['key' => 'value'];
        $request = new Request();
        $request->attributes->set('oro_user_emailsettings', $data);
        $this->requestStack->push($request);

        $this->eventMock->expects($this->once())
            ->method('setData')
            ->with($this->equalTo($data));

        $this->subscriber->preSubmit($this->eventMock);
    }
}
