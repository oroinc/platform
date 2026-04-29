<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\EventListener;

use Oro\Bundle\IntegrationBundle\Event\WebhookNotifyEvent;
use Oro\Bundle\IntegrationBundle\EventListener\WebhookNotificationEventListener;
use Oro\Bundle\IntegrationBundle\Model\WebhookNotifierInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WebhookNotificationEventListenerTest extends TestCase
{
    private WebhookNotifierInterface&MockObject $webhookNotifier;
    private WebhookNotificationEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->webhookNotifier = $this->createMock(WebhookNotifierInterface::class);
        $this->listener = new WebhookNotificationEventListener($this->webhookNotifier);
    }

    public function testOnNotifyForwardsToNotifier(): void
    {
        $event = new WebhookNotifyEvent('product.created', ['id' => 1, 'name' => 'Widget']);

        $this->webhookNotifier->expects(self::once())
            ->method('sendNotification')
            ->with('product.created', ['id' => 1, 'name' => 'Widget']);

        $this->listener->onNotify($event);
    }

    public function testOnNotifyDoesNothingWhenDisabled(): void
    {
        $this->listener->setEnabled(false);

        $this->webhookNotifier->expects(self::never())
            ->method('sendNotification');

        $this->listener->onNotify(new WebhookNotifyEvent('product.created', []));
    }

    public function testListenerIsEnabledByDefault(): void
    {
        $event = new WebhookNotifyEvent('order.updated', ['id' => 42]);

        $this->webhookNotifier->expects(self::once())
            ->method('sendNotification');

        $this->listener->onNotify($event);
    }
}
