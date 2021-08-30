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
    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var RequestStack */
    private $requestStack;

    /** @var FormEvent|\PHPUnit\Framework\MockObject\MockObject */
    private $eventMock;

    /** @var UserImapConfigSubscriber */
    private $subscriber;

    protected function setUp(): void
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
                FormEvents::PRE_SUBMIT => 'preSubmit',
            ],
            $this->subscriber->getSubscribedEvents()
        );
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

        $this->manager->expects($this->once())
            ->method('find')
            ->with('OroUserBundle:User', $id)
            ->willReturn($user);

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

        $this->manager->expects($this->never())
            ->method('find');

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
            ->willReturn($eventData);
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
