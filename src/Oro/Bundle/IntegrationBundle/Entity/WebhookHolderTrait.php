<?php

namespace Oro\Bundle\IntegrationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;

/**
 * Represents an entity holding consumer webhook relation.
 */
trait WebhookHolderTrait
{
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.integration.webhookconsumersettings.entity_label']])]
    #[ORM\ManyToOne(targetEntity: WebhookConsumerSettings::class, cascade: ['all'])]
    #[ORM\JoinColumn(name: 'webhook_consumer_settings_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?WebhookConsumerSettings $webhook = null;
}
