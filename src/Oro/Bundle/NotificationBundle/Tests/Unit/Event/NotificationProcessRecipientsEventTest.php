<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Event;

use Oro\Bundle\NotificationBundle\Event\NotificationProcessRecipientsEvent;
use Oro\Bundle\NotificationBundle\Model\EmailAddressWithContext;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;

class NotificationProcessRecipientsEventTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor(): void
    {
        $recipients = [new EmailAddressWithContext('test1@mail.com'), new EmailAddressWithContext('test2@mail.com')];
        $entity = new TestActivity();
        $event = new NotificationProcessRecipientsEvent($entity, $recipients);

        self::assertEquals($recipients, $event->getRecipients());
        self::assertEquals($entity, $event->getEntity());
    }

    public function testSetter(): void
    {
        $entity = new TestActivity();
        $event = new NotificationProcessRecipientsEvent($entity, []);

        $recipients = [new EmailAddressWithContext('test1@mail.com'), new EmailAddressWithContext('test2@mail.com')];

        $event->setRecipients($recipients);

        self::assertEquals($recipients, $event->getRecipients());
        self::assertEquals($entity, $event->getEntity());
    }
}
