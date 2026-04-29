<?php

namespace Oro\Bundle\IntegrationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\IntegrationBundle\Entity\Repository\WebhookProducerSettingsRepository;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\SecurityBundle\DoctrineExtension\Dbal\Types\CryptedStringType;
use Oro\Bundle\UserBundle\Entity\Ownership\AuditableUserAwareTrait;
use Oro\Component\DoctrineUtils\ORM\Id\UuidGenerator;

/**
 * WebhookProducerSettings entity stores webhook notification configurations.
 */
#[ORM\Entity(repositoryClass: WebhookProducerSettingsRepository::class)]
#[ORM\Table(name: 'oro_integration_webhook_producer_settings')]
#[ORM\Index(columns: ['topic', 'enabled'], name: 'idx_webhook_producer_settings_search')]
#[Config(
    routeName: 'oro_integration_webhook_producer_settings_index',
    routeView: 'oro_integration_webhook_producer_settings_view',
    routeUpdate: 'oro_integration_webhook_producer_settings_update',
    defaultValues: [
        'entity' => ['icon' => 'fa-bell'],
        'dataaudit' => ['auditable' => true],
        'ownership' => [
            'owner_type' => 'USER',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'user_owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'account_management'],
        'sanitize' => ['rule' => 'truncate']
    ]
)]
class WebhookProducerSettings implements DatesAwareInterface, ExtendEntityInterface, OrganizationAwareInterface
{
    use DatesAwareTrait;
    use ExtendEntityTrait;
    use AuditableUserAwareTrait;

    #[ORM\Column(name: 'id', type: Types::GUID)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?string $id = null;

    #[ORM\Column(name: 'notification_url', type: Types::STRING, length: 2048, nullable: false)]
    private ?string $notificationUrl = null;

    #[ORM\Column(name: 'topic', type: Types::STRING, length: 255, nullable: false)]
    private ?string $topic = null;

    #[ORM\Column(name: 'secret', type: CryptedStringType::TYPE, length: 255, nullable: true)]
    private ?string $secret = null;

    #[ORM\Column(name: 'enabled', type: Types::BOOLEAN, nullable: false, options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(name: 'verify_ssl', type: Types::BOOLEAN, nullable: false, options: ['default' => true])]
    private bool $verifySsl = true;

    #[ORM\Column(name: 'format', type: Types::STRING, length: 255, nullable: false)]
    private ?string $format = null;

    #[ORM\Column(name: 'is_system', type: Types::BOOLEAN, nullable: false, options: ['default' => false])]
    private bool $system = false;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getNotificationUrl(): ?string
    {
        return $this->notificationUrl;
    }

    public function setNotificationUrl(?string $notificationUrl): self
    {
        $this->notificationUrl = $notificationUrl;

        return $this;
    }

    public function getTopic(): ?string
    {
        return $this->topic;
    }

    public function setTopic(?string $topic): self
    {
        $this->topic = $topic;

        return $this;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function setSecret(?string $secret): self
    {
        $this->secret = $secret;

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

    public function isVerifySsl(): bool
    {
        return $this->verifySsl;
    }

    public function setVerifySsl(bool $verifySsl): self
    {
        $this->verifySsl = $verifySsl;

        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function isSystem(): bool
    {
        return $this->system;
    }

    public function setSystem(bool $system): self
    {
        $this->system = $system;

        return $this;
    }
}
