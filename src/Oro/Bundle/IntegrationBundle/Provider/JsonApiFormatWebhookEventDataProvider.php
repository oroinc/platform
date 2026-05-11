<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * Converted entity to the event data (array) applicable for the webhook notification.
 *
 * Use the JSON:API data to eliminate differences between the webhook and the API calls.
 * This should simplify the integration logic.
 */
class JsonApiFormatWebhookEventDataProvider extends AbstractWebhookEventDataProvider
{
    public function __construct(
        ActionProcessorBagInterface $actionProcessorBag,
        private ConfigManager $entityConfigManager
    ) {
        parent::__construct($actionProcessorBag);
    }

    protected function getRequestTypes(): array
    {
        return [RequestType::JSON_API, RequestType::REST];
    }

    protected function prepareContext(Context $context, string $entityClass, int|string $entityId): void
    {
        parent::prepareContext($context, $entityClass, $entityId);

        $include = $this->getInclude($entityClass);
        if (!empty($include)) {
            $context->getFilterValues()->set(
                'include',
                FilterValue::createFromSource('include', 'include', $include)
            );
        }
    }

    private function getInclude(string $entityClass): ?string
    {
        $include = null;
        if ($this->entityConfigManager->hasConfig($entityClass)) {
            $entityConfig = $this->entityConfigManager->getEntityConfig(
                WebhookConfigurationProvider::ENTITY_CONFIG_SCOPE,
                $entityClass
            );
            $include = $entityConfig->get('webhook_relations_includes');
        }

        return $include;
    }

    protected function getFallbackData(string $className, int|string $id): array
    {
        return [
            'data' => [
                'type' => $className,
                'id' => $id
            ]
        ];
    }
}
