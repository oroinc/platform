<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\EventListener\UserImapConfigSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class UserImapConfigSubscriberTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private RequestStack $requestStack;
    private TokenAccessorInterface&MockObject $tokenAccessor;
    private UserImapConfigSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->requestStack = new RequestStack();
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->subscriber = new UserImapConfigSubscriber($this->doctrine, $this->requestStack, $this->tokenAccessor);
    }

    public function testSubscribedEvents(): void
    {
        $this->assertEquals(
            [
                FormEvents::PRE_SET_DATA => 'preSetData',
                FormEvents::PRE_SUBMIT => 'preSubmit',
            ],
            $this->subscriber->getSubscribedEvents()
        );
    }

    public function testPreSetDataForUserConfig(): void
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

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(User::class, $id)
            ->willReturn($user);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(User::class)
            ->willReturn($em);

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('setData')
            ->with($this->equalTo($user));

        $this->subscriber->preSetData($event);
    }

    public function testPreSetDataForProfileConfig(): void
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

        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('setData')
            ->with($this->equalTo($user));

        $this->subscriber->preSetData($event);

        $this->assertSame($organization, $user->getCurrentOrganization());
    }

    public function testPreSubmit(): void
    {
        $data = ['imapConfiguration' => ['folders' => ['some folder']]];
        $eventData = ['imapConfiguration' => ['imapPort' => '111']];
        $request = new Request();
        $request->attributes->set('oro_user_emailsettings', $data);
        $this->requestStack->push($request);

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($eventData);
        $event->expects($this->once())
            ->method('setData')
            ->with($this->equalTo(
                [
                    'imapConfiguration' => [
                        'folders'  => ['some folder'],
                        'imapPort' => '111'
                    ]
                ]
            ));

        $this->subscriber->preSubmit($event);
    }
}
