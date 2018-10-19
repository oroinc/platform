<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\EventListener\UserImapConfigSubscriber;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class UserImapConfigSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $manager;

    /** @var RequestStack */
    protected $requestStack;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $eventMock;

    /** @var  UserImapConfigSubscriber */
    protected $subscriber;

    protected function setUp()
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->requestStack = new RequestStack();
        $this->manager = $this->createMock(EntityManager::class);
        $this->eventMock = $this->createMock(FormEvent::class);

        $this->subscriber = new UserImapConfigSubscriber($this->manager, $this->requestStack, $this->tokenAccessor);
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
        $organization = new Organization();
        $request = new Request();
        $request->attributes->add(
            [
                '_route' => 'oro_user_config',
                'id' => $id
            ]
        );
        $this->requestStack->push($request);

        $this->tokenAccessor->expects($this->never())
            ->method('getUser');
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

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
        $organization = new Organization();
        $request = new Request();
        $request->attributes->add(
            [
                '_route' => 'oro_user_profile_configuration',
            ]
        );
        $this->requestStack->push($request);

        $this->manager->expects($this->never())->method('find');

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->eventMock->expects($this->once())
            ->method('setData')
            ->with($this->equalTo($user));

        $this->subscriber->preSetData($this->eventMock);

        $this->assertSame($organization, $user->getCurrentOrganization());
    }

    public function testPreSubmit()
    {
        $data = ['imapConfiguration' => ['folders' => ['some folder']]];
        $eventData = ['imapConfiguration' => ['imapPort' => '111']];
        $request = new Request();
        $request->attributes->set('oro_user_emailsettings', $data);
        $this->requestStack->push($request);

        $this->eventMock->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($eventData));
        $this->eventMock->expects($this->once())
            ->method('setData')
            ->with($this->equalTo(
                [
                    'imapConfiguration' => [
                        'folders'  => ['some folder'],
                        'imapPort' => '111'
                    ]
                ]
            ));

        $this->subscriber->preSubmit($this->eventMock);
    }
}
