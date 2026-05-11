<?php

namespace Oro\Bundle\IntegrationBundle\Form\DataTransformer;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\WebhookConsumerSettings;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms WebhookConsumerSettings entities to and from UUID identifiers while ensuring UUID persistence
 * throughout the form lifecycle.
 *
 * The transformer manages UUID generation and persistence across different form states:
 * - When displaying a form for a new entity (initial render), generates a fresh UUID that will be used
 *   consistently for that entity
 * - When form submission fails validation, preserves the previously generated UUID to maintain consistency
 * - When editing an existing entity, uses the UUID already persisted in the database
 *
 * This approach guarantees that each WebhookConsumerSettings entity maintains a stable identifier from
 * the moment the form is first displayed through validation attempts until final persistence.
 */
class WebhookConsumerSettingsDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private ManagerRegistry $registry,
        private string $processor
    ) {
    }

    public function transform(mixed $value): mixed
    {
        if ($value instanceof WebhookConsumerSettings) {
            return $value->getId();
        }

        return UUIDGenerator::v4();
    }

    public function reverseTransform(mixed $value): mixed
    {
        if (!$value) {
            return null;
        }

        $webhook = $this->registry->getRepository(WebhookConsumerSettings::class)->find($value);
        if (!$webhook) {
            $webhook = new WebhookConsumerSettings();
            $webhook->setId($value);
            $webhook->setProcessor($this->processor);
        }

        return $webhook;
    }
}
