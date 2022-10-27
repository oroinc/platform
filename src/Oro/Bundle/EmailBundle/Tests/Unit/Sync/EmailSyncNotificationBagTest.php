<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sync;

use Oro\Bundle\EmailBundle\Sync\EmailSyncNotificationAlert;
use Oro\Bundle\EmailBundle\Sync\EmailSyncNotificationBag;

class EmailSyncNotificationBagTest extends \PHPUnit\Framework\TestCase
{
    public function testNotificationBag(): void
    {
        $bag = new EmailSyncNotificationBag();

        $alert1 = EmailSyncNotificationAlert::createForAuthFail('test1');
        $alert2 = EmailSyncNotificationAlert::createForAuthFail('test2');

        self::assertEmpty($bag->getNotifications());
        $bag->addNotification($alert1);
        self::assertEquals([$alert1], $bag->getNotifications());
        $bag->addNotification($alert2);
        self::assertEquals([$alert1, $alert2], $bag->getNotifications());
        $bag->emptyNotifications();
        self::assertEmpty($bag->getNotifications());
    }
}
