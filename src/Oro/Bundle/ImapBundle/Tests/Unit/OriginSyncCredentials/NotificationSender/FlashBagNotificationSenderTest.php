<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\OriginSyncCredentials\NotificationSender;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\OriginSyncCredentials\NotificationSender\FlashBagNotificationSender;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

class FlashBagNotificationSenderTest extends TestCase
{
    private FlashBag $flashBag;
    private TranslatorInterface&MockObject $translator;
    private FlashBagNotificationSender $sender;

    #[\Override]
    protected function setUp(): void
    {
        $this->flashBag = new FlashBag();
        $this->translator = $this->createMock(TranslatorInterface::class);

        $session = $this->createMock(Session::class);
        $request = new Request();
        $request->setSession($session);
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($this->flashBag);

        $this->sender = new FlashBagNotificationSender($requestStack, $this->translator);
    }

    public function testSendNotificationForSystemOrigin(): void
    {
        $origin = new UserEmailOrigin();
        $origin->setUser('test@example.com');
        $origin->setImapHost('example.com');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                'oro.imap.sync.flash_message.system_box_failed',
                [
                    '%username%' => 'test@example.com',
                    '%host%' => 'example.com'
                ]
            )
            ->willReturn('translated_message_system');

        $this->sender->sendNotification($origin);

        $this->assertEquals(['error' => ['translated_message_system']], $this->flashBag->all());
    }

    public function testSendNotification(): void
    {
        $origin = new UserEmailOrigin();
        $origin->setUser('test@example.com');
        $origin->setImapHost('example.com');

        $user = new User();
        $origin->setOwner($user);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                'oro.imap.sync.flash_message.user_box_failed',
                [
                    '%username%' => 'test@example.com',
                    '%host%' => 'example.com'
                ]
            )
            ->willReturn('translated_message');

        $this->sender->sendNotification($origin);

        $this->assertEquals(['error' => ['translated_message']], $this->flashBag->all());
    }
}
