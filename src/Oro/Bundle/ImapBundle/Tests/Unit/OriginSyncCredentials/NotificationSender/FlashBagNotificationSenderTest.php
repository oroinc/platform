<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\OriginSyncCredentials\NotificationSender;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\OriginSyncCredentials\NotificationSender\FlashBagNotificationSender;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

class FlashBagNotificationSenderTest extends \PHPUnit\Framework\TestCase
{
    /** @var FlashBagNotificationSender */
    private $sender;

    /** @var FlashBag */
    private $flashBag;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    protected function setUp()
    {
        $requestStack = new RequestStack();
        $request = new Request();
        $session = $this->createMock(Session::class);
        $this->flashBag = new FlashBag();

        $requestStack->push($request);
        $request->setSession($session);
        $session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($this->flashBag);

        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->sender = new FlashBagNotificationSender($requestStack, $this->translator);
    }

    public function testSendNotificationForSystemOrigin()
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

    public function testSendNotification()
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
