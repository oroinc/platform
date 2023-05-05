<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Event;

use Oro\Bundle\NotificationBundle\Event\NotificationProcessRecipientsEvent;
use Oro\Bundle\NotificationBundle\Helper\WebsiteAwareEntityHelper;
use Oro\Bundle\NotificationBundle\Model\EmailAddressWithContext;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;

class NotificationProcessRecipientsEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var WebsiteAwareEntityHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $websiteAware;

    protected function setUp(): void
    {
        $this->websiteAware = $this->createMock(WebsiteAwareEntityHelper::class);
    }

    public function testConstructor(): void
    {
        $recipients = [new EmailAddressWithContext('test1@mail.com'), new EmailAddressWithContext('test2@mail.com')];
        $entity = new TestActivity();
        $event = new NotificationProcessRecipientsEvent($entity, $recipients, $this->websiteAware);

        self::assertEquals($recipients, $event->getRecipients());
        self::assertEquals($entity, $event->getEntity());
    }

    public function testSetter(): void
    {
        $entity = new TestActivity();
        $event = new NotificationProcessRecipientsEvent($entity, [], $this->websiteAware);

        $recipients = [new EmailAddressWithContext('test1@mail.com'), new EmailAddressWithContext('test2@mail.com')];

        $event->setRecipients($recipients);

        self::assertEquals($recipients, $event->getRecipients());
        self::assertEquals($entity, $event->getEntity());
    }
}
