<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Stub;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\OriginSyncCredentials\NotificationSenderInterface;

class TestNotificationSender implements NotificationSenderInterface
{
    public $processedOrigins = [];

    public function sendNotification(UserEmailOrigin $emailOrigin)
    {
        $this->processedOrigins[] = $emailOrigin;
    }
}
