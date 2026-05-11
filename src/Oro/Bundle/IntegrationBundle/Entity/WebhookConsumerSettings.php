<?php

namespace Oro\Bundle\IntegrationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * WebhookConsumerSettings entity stores webhook consumer configurations.
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_integration_webhook_consumer_settings')]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-bell'],
        'dataaudit' => ['auditable' => true]
    ]
)]
class WebhookConsumerSettings implements DatesAwareInterface, ExtendEntityInterface
{
    use DatesAwareTrait;
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::GUID)]
    #[ORM\Id]
    private ?string $id = null;

    #[ORM\Column(name: 'processor', type: Types::STRING, length: 255, nullable: false)]
    private ?string $processor = null;

    #[ORM\Column(name: 'enabled', type: Types::BOOLEAN, nullable: false, options: ['default' => true])]
    private bool $enabled = true;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getProcessor(): ?string
    {
        return $this->processor;
    }

    public function setProcessor(string $processor): self
    {
        $this->processor = $processor;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }
}
