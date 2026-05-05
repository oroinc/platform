<?php

namespace Oro\Bundle\IntegrationBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfterListenerInterface;
use Oro\Bundle\IntegrationBundle\Provider\WebhookConfigurationProvider;

/**
 * Adds topic model to the datagrid records.
 */
final class WebhookProducerSettingsDatagridListener implements OrmResultAfterListenerInterface
{
    public function __construct(
        private WebhookConfigurationProvider $webhookConfigurationProvider
    ) {
    }

    #[\Override]
    public function onResultAfter(OrmResultAfter $event): void
    {
        $topics = $this->webhookConfigurationProvider->getAvailableTopics();
        foreach ($event->getRecords() as $record) {
            $topicName = $record->getValue('topic');
            $record->addData(['topicModel' => $topics[$topicName] ?? null]);
        }
    }
}
