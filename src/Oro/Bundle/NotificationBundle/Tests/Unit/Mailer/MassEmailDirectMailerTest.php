<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Mailer;

use Oro\Bundle\EmailBundle\Mailer\DirectMailer;
use Oro\Bundle\NotificationBundle\Entity\SpoolItem;
use Oro\Bundle\NotificationBundle\Event\NotificationSentEvent;
use Oro\Bundle\NotificationBundle\Mailer\MassEmailDirectMailer;
use Oro\Bundle\NotificationBundle\Model\MassNotificationSender;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MassEmailDirectMailerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DirectMailer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $directMailer;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $eventDispatcher;

    /**
     * @var MassEmailDirectMailer
     */
    private $mailer;

    protected function setUp()
    {
        $this->directMailer = $this->createMock(DirectMailer::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->mailer = new MassEmailDirectMailer($this->directMailer, $this->eventDispatcher);
    }

    public function testSend()
    {
        $failedRecipients = ['some data'];
        $message = \Swift_Message::newInstance();
        $sent = 777;
        $this->directMailer->expects($this->once())
            ->method('send')
            ->with($message, $failedRecipients)
            ->willReturn($sent);

        $spoolItem = new SpoolItem();
        $spoolItem
            ->setLogType(MassNotificationSender::NOTIFICATION_LOG_TYPE)
            ->setMessage($message);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(NotificationSentEvent::NAME, new NotificationSentEvent($spoolItem, $sent));

        self::assertEquals($sent, $this->mailer->send($message, $failedRecipients));
    }

    public function testRegisterPlugin()
    {
        /** @var \Swift_Events_EventListener $plugin */
        $plugin = $this->createMock(\Swift_Events_EventListener::class);
        $this->directMailer->expects($this->once())
            ->method('registerPlugin')
            ->with($plugin);

        $this->mailer->registerPlugin($plugin);
    }

    public function testGetTransport()
    {
        /** @var \Swift_Transport $plugin */
        $transport = $this->createMock(\Swift_Transport::class);
        $this->directMailer->expects($this->once())
            ->method('getTransport')
            ->willReturn($transport);

        self::assertEquals($transport, $this->mailer->getTransport());
    }

    public function testCreateMessage()
    {
        $service = 'some service';
        $object = new \stdClass();
        $this->directMailer->expects($this->once())
            ->method('createMessage')
            ->with($service)
            ->willReturn($object);

        self::assertEquals($object, $this->mailer->createMessage($service));
    }
}
