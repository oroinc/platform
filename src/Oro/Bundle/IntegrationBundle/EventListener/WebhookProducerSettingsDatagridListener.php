<?php

namespace Oro\Bundle\IntegrationBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\IntegrationBundle\Provider\WebhookConfigurationProvider;

/**
 * Adds topic model to the datagrid records.
 */
final class WebhookProducerSettingsDatagridListener
{
    public function __construct(
        private WebhookConfigurationProvider $webhookConfigurationProvider
    ) {
    }

    public function onResultAfter(OrmResultAfter $event)
    {
        $topics = $this->webhookConfigurationProvider->getAvailableTopics();
        foreach ($event->getRecords() as $record) {
            $topicName = $record->getValue('topic');
            $record->addData(['topicModel' => $topics[$topicName] ?? null]);
        }
    }
}
