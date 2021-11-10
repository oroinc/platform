<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Model;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\NotificationBundle\Model\TemplateMassNotification;

class TemplateMassNotificationTest extends \PHPUnit\Framework\TestCase
{
    private const SENDER_EMAIL = 'some@some.com';
    private const SENDER_NAME = 'some';

    public function testGetTemplateCriteria(): void
    {
        $recipient = $this->createMock(EmailHolderInterface::class);
        $criteria = new EmailTemplateCriteria('template name');

        $notification = new TemplateMassNotification(
            From::emailAddress(self::SENDER_EMAIL, self::SENDER_NAME),
            [$recipient],
            $criteria
        );
        self::assertEquals($criteria, $notification->getTemplateCriteria());
    }

    public function testGetRecipients(): void
    {
        $recipient = $this->createMock(EmailHolderInterface::class);

        $notification = new TemplateMassNotification(
            From::emailAddress(self::SENDER_EMAIL, self::SENDER_NAME),
            [$recipient],
            new EmailTemplateCriteria('template name')
        );

        self::assertEquals([$recipient], $notification->getRecipients());
    }

    public function testGetSubject(): void
    {
        $recipient = $this->createMock(EmailHolderInterface::class);
        $subject = 'Subject';

        $notification = new TemplateMassNotification(
            From::emailAddress(self::SENDER_EMAIL, self::SENDER_NAME),
            [$recipient],
            new EmailTemplateCriteria('template name'),
            $subject
        );

        self::assertEquals($subject, $notification->getSubject());
    }

    public function testGetSender(): void
    {
        $recipient = $this->createMock(EmailHolderInterface::class);

        $sender = From::emailAddress(self::SENDER_EMAIL, self::SENDER_NAME);
        $notification = new TemplateMassNotification(
            $sender,
            [$recipient],
            new EmailTemplateCriteria('template name')
        );

        self::assertEquals($sender, $notification->getSender());
    }
}
