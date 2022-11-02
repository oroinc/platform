<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Environment;

use Oro\Bundle\NotificationBundle\Event\Handler\EmailNotificationHandler as BaseEmailNotificationHandler;
use Oro\Bundle\NotificationBundle\Event\NotificationEvent;

class EmailNotificationHandler extends BaseEmailNotificationHandler
{
    private $handleCount = 0;

    public function getHandleCount(): int
    {
        return $this->handleCount;
    }

    public function clearHandleCount(): void
    {
        $this->handleCount = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(NotificationEvent $event, array $matchedNotifications): void
    {
        $this->handleCount++;

        parent::handle($event, $matchedNotifications);
    }
}
