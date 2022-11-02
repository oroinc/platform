<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\EventListener\ChangePasswordSubscriber;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ChangePasswordSubscriberTest extends FormIntegrationTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var ChangePasswordSubscriber */
    private $subscriber;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->token = $this->createMock(TokenInterface::class);

        $this->subscriber = new ChangePasswordSubscriber($this->factory, $this->tokenAccessor);
    }

    /**
     * test getSubscribedEvents
     */
    public function testSubscribedEvents()
    {
        $this->assertEquals(
            [
                FormEvents::POST_SUBMIT => 'onSubmit',
                FormEvents::PRE_SUBMIT   => 'preSubmit'
            ],
            $this->subscriber->getSubscribedEvents()
        );
    }

    /**
     * Test onSubmit
     */
    public function testOnSubmit()
    {
        $eventMock = $this->createMock(FormEvent::class);
        $formMock = $this->createMock(FormInterface::class);
        $parentFormMock = $this->createMock(FormInterface::class);

        $formMock->expects($this->once())
            ->method('getParent')
            ->willReturn($parentFormMock);

        $formPlainPassword = $this->createMock(FormInterface::class);
        $formPlainPassword->expects($this->once())
            ->method('getData')
            ->willReturn('123123');

        $formMock->expects($this->once())
            ->method('get')
            ->with($this->equalTo('plainPassword'))
            ->willReturn($formPlainPassword);

        $currentUser = $this->createMock(User::class);
        $userMock = $currentUser;

        $userMock->expects($this->exactly(3))
            ->method('getId')
            ->willReturn(1);

        $this->token->expects($this->any())
            ->method('getUser')
            ->willReturn($currentUser);

        $this->tokenAccessor->expects($this->once())
            ->method('getToken')
            ->willReturn($this->token);

        $parentFormMock->expects($this->once())
            ->method('getData')
            ->willReturn($userMock);

        $eventMock->expects($this->once())
            ->method('getForm')
            ->willReturn($formMock);

        $this->subscriber->onSubmit($eventMock);
    }

    /**
     * Test preSubmit
     *
     * @dataProvider preSubmitProvider
     */
    public function testPreSubmit($mode, $data)
    {
        $eventMock = $this->createMock(FormEvent::class);

        $formMock = $this->createMock(FormInterface::class);

        $eventMock->expects($this->once())
            ->method('getForm')
            ->willReturn($formMock);
        $eventMock->expects($this->once())
            ->method('getData')
            ->willReturn($data);

        if ($mode) {
            $formMock->expects($this->once())
                ->method('remove')
                ->with('currentPassword');

            $formMock->expects($this->once())
                ->method('add')
                ->with($this->isInstanceOf(Form::class));
        } else {
            $formMock->expects($this->never())
                ->method('remove');

            $formMock->expects($this->never())
                ->method('add');
        }

        $this->subscriber->preSubmit($eventMock);
    }

    public function preSubmitProvider(): array
    {
        return [
            [
                true,
                [
                    'currentPassword' => null,
                    'plainPassword' => ['first' => null]
                ]
            ],
            [
                false,
                [
                    'currentPassword' => '123123',
                    'plainPassword' => ['first' => '32321']
                ]
            ]
        ];
    }

    /**
     * Test bad scenario for isCurrentUser
     */
    public function testIsCurrentUserFalse()
    {
        $userMock = $this->createMock(User::class);
        $userMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->token->expects($this->any())
            ->method('getUser')
            ->willReturn(null);

        $this->tokenAccessor->expects($this->once())
            ->method('getToken')
            ->willReturn($this->token);

        ReflectionUtil::callMethod($this->subscriber, 'isCurrentUser', [$userMock]);
    }
}
