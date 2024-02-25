<?php

namespace Oro\Bundle\NotificationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroNotificationBundle_Entity_NotificationAlert;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareTrait;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedAtAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Represents a storage of a notification alerts.
 *
 * @mixin OroNotificationBundle_Entity_NotificationAlert
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_notification_alert')]
#[Config(
    routeName: 'oro_notification_notificationalert_index',
    defaultValues: [
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'organization',
            'owner_column_name' => 'organization_id'
        ],
        'security' => [
            'type' => 'ACL',
            'group_name' => '',
            'category' => 'account_management',
            'permissions' => 'VIEW,DELETE'
        ],
        'activity' => ['immutable' => true],
        'attachment' => ['immutable' => true]
    ]
)]
class NotificationAlert implements ExtendEntityInterface
{
    use CreatedAtAwareTrait;
    use UpdatedAtAwareTrait;
    use ExtendEntityTrait;

    /**
     * @var string
     */
    #[ORM\Column(name: 'id', type: Types::GUID)]
    #[ORM\Id]
    private $id;

    #[ORM\Column(name: 'alert_type', type: Types::STRING, length: 20, nullable: true)]
    private ?string $alertType = null;

    #[ORM\Column(name: 'source_type', type: Types::STRING, length: 50)]
    private ?string $sourceType = null;

    #[ORM\Column(name: 'resource_type', type: Types::STRING, length: 255)]
    private ?string $resourceType = null;

    #[ORM\Column(name: 'operation', type: Types::STRING, length: 50, nullable: true)]
    private ?string $operation = null;

    #[ORM\Column(name: 'step', type: Types::STRING, length: 50, nullable: true)]
    private ?string $step = null;

    #[ORM\Column(name: 'item_id', type: Types::INTEGER, length: 255, nullable: true)]
    private ?int $itemId = null;

    #[ORM\Column(name: 'external_id', type: Types::STRING, length: 255, nullable: true)]
    private ?string $externalId = null;

    #[ORM\Column(name: 'is_resolved', type: Types::BOOLEAN, nullable: false, options: ['default' => false])]
    private ?bool $resolved = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(name: 'message', type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Organization $organization = null;

    /**
     * @var array
     */
    #[ORM\Column(name: 'additional_info', type: Types::JSON, nullable: true)]
    private $additionalInfo = [];

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    public function setSourceType(string $sourceType): void
    {
        $this->sourceType = $sourceType;
    }

    public function getResourceType(): string
    {
        return $this->resourceType;
    }

    public function setResourceType(string $resourceType): void
    {
        $this->resourceType = $resourceType;
    }

    public function getOperation(): ?string
    {
        return $this->operation;
    }

    public function setOperation(string $operation): void
    {
        $this->operation = $operation;
    }

    public function getStep(): ?string
    {
        return $this->step;
    }

    public function setStep(string $step): void
    {
        $this->step = $step;
    }

    public function getItemId(): ?int
    {
        return $this->itemId;
    }

    public function setItemId(int $itemId): void
    {
        $this->itemId = $itemId;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): void
    {
        $this->externalId = $externalId;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getAlertType(): ?string
    {
        return $this->alertType;
    }

    public function setAlertType(string $alertType): void
    {
        $this->alertType = $alertType;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function setResolved($resolved): void
    {
        $this->resolved = $resolved;
        if ($resolved) {
            $this->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        }
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function setOrganization(OrganizationInterface $organization): void
    {
        $this->organization = $organization;
    }

    public function getOrganization(): OrganizationInterface
    {
        return $this->organization;
    }

    public function getAdditionalInfo(): array
    {
        return $this->additionalInfo ?: [];
    }

    public function setAdditionalInfo(array $additionalInfo): void
    {
        $this->additionalInfo = $additionalInfo;
    }

    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        if (!$this->updatedAt) {
            $this->updatedAt = clone $this->createdAt;
        }
    }
}
