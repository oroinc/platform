<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Monolog;

use Oro\Bundle\LoggerBundle\Mailer\NoRecipientPlugin;

class NoRecipientPluginTest extends \PHPUnit\Framework\TestCase
{
    public function testBeforeSendPerformedWithoutRecipient()
    {
        $message = new \Swift_Message();

        $event = $this->getMockBuilder(\Swift_Events_SendEvent::class)->disableOriginalConstructor()->getMock();
        $event->expects($this->once())
            ->method('getMessage')
            ->willReturn($message);

        $event->expects($this->once())
            ->method('cancelBubble');

        $plugin = new NoRecipientPlugin();
        $plugin->beforeSendPerformed($event);
    }

    public function testBeforeSendPerformedWithRecipient()
    {
        $message = new \Swift_Message();
        $message->setTo('recipient@example.com');

        $event = $this->getMockBuilder(\Swift_Events_SendEvent::class)->disableOriginalConstructor()->getMock();
        $event->expects($this->once())
            ->method('getMessage')
            ->willReturn($message);

        $event->expects($this->never())
            ->method('cancelBubble');

        $plugin = new NoRecipientPlugin();
        $plugin->beforeSendPerformed($event);
    }
}
