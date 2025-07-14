<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Event;

use Oro\Bundle\NotificationBundle\Event\NotificationProcessRecipientsEvent;
use Oro\Bundle\NotificationBundle\Model\EmailAddressWithContext;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use PHPUnit\Framework\TestCase;

class NotificationProcessRecipientsEventTest extends TestCase
{
    public function testConstructor(): void
    {
        $entity = new TestActivity();
        $recipients = [new EmailAddressWithContext('test1@mail.com'), new EmailAddressWithContext('test2@mail.com')];
        $event = new NotificationProcessRecipientsEvent($entity, $recipients);

        self::assertSame($entity, $event->getEntity());
        self::assertSame($recipients, $event->getRecipients());
    }

    public function testSetRecipients(): void
    {
        $recipients = [new EmailAddressWithContext('test1@mail.com'), new EmailAddressWithContext('test2@mail.com')];

        $event = new NotificationProcessRecipientsEvent(new TestActivity(), []);
        $event->setRecipients($recipients);

        self::assertSame($recipients, $event->getRecipients());
    }
}
