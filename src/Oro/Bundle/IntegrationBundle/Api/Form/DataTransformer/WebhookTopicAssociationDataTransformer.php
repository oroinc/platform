<?php

namespace Oro\Bundle\IntegrationBundle\Api\Form\DataTransformer;

use Oro\Bundle\ApiBundle\Form\DataTransformer\AbstractAssociationTransformer;
use Oro\Bundle\IntegrationBundle\Api\Model\WebhookTopic;

/**
 * The data transformer for {@see \Oro\Bundle\IntegrationBundle\Api\Form\Type\WebhookTopicAssociationType}.
 */
class WebhookTopicAssociationDataTransformer extends AbstractAssociationTransformer
{
    #[\Override]
    protected function getAcceptableEntityClassNames(): ?array
    {
        return [WebhookTopic::class];
    }

    #[\Override]
    protected function isEntityIdAcceptable(mixed $entityId): bool
    {
        return \is_string($entityId) && '' !== trim($entityId);
    }

    #[\Override]
    protected function getEntity(string $entityClass, mixed $entityId): string
    {
        return $entityId;
    }
}
