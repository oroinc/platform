<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\IntegrationBundle\EventListener\WebhookProducerSettingsDatagridListener;
use Oro\Bundle\IntegrationBundle\Model\WebhookTopic;
use Oro\Bundle\IntegrationBundle\Provider\WebhookConfigurationProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WebhookProducerSettingsDatagridListenerTest extends TestCase
{
    private WebhookConfigurationProvider&MockObject $webhookConfigurationProvider;
    private WebhookProducerSettingsDatagridListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->webhookConfigurationProvider = $this->createMock(WebhookConfigurationProvider::class);
        $this->listener = new WebhookProducerSettingsDatagridListener($this->webhookConfigurationProvider);
    }

    public function testOnResultAfterWithNoRecords(): void
    {
        $this->webhookConfigurationProvider->expects(self::once())
            ->method('getAvailableTopics')
            ->willReturn([]);

        $event = new OrmResultAfter($this->createMock(DatagridInterface::class), []);

        $this->listener->onResultAfter($event);

        self::assertEmpty($event->getRecords());
    }

    public function testOnResultAfterAddsTopicModelWhenTopicIsKnown(): void
    {
        $topicName = 'account.created';
        $topicModel = new WebhookTopic($topicName, 'Account Created');

        $this->webhookConfigurationProvider->expects(self::once())
            ->method('getAvailableTopics')
            ->willReturn([$topicName => $topicModel]);

        $record = new ResultRecord(['topic' => $topicName]);
        $event = new OrmResultAfter($this->createMock(DatagridInterface::class), [$record]);

        $this->listener->onResultAfter($event);

        self::assertSame($topicModel, $record->getValue('topicModel'));
    }

    public function testOnResultAfterSetsTopicModelToNullWhenTopicIsUnknown(): void
    {
        $this->webhookConfigurationProvider->expects(self::once())
            ->method('getAvailableTopics')
            ->willReturn([]);

        $record = new ResultRecord(['topic' => 'unknown.topic']);
        $event = new OrmResultAfter($this->createMock(DatagridInterface::class), [$record]);

        $this->listener->onResultAfter($event);

        self::assertNull($record->getValue('topicModel'));
    }
}
