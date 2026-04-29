<?php

namespace Oro\Bundle\IntegrationBundle\EventListener;

use Oro\Bundle\IntegrationBundle\Event\WebhookNotifyEvent;
use Oro\Bundle\IntegrationBundle\Model\WebhookNotifierInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;

/**
 * Handles the invocation of webhook notification events by sending notifications
 * through the provided WebhookNotifier instance.
 */
final class WebhookNotificationEventListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    public function __construct(
        private WebhookNotifierInterface $webhookNotifier
    ) {
    }

    public function onNotify(WebhookNotifyEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->webhookNotifier->sendNotification(
            $event->getTopic(),
            $event->getEventData()
        );
    }
}
