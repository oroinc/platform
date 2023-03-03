<?php

namespace Oro\Bundle\NotificationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareTrait;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedAtAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Represents a storage of a notification alerts.
 *
 * @ORM\Table(name="oro_notification_alert")
 * @ORM\Entity
 * @Config(
 *      routeName="oro_notification_notificationalert_index",
 *      defaultValues={
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="organization",
 *              "owner_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="",
 *              "category"="account_management",
 *              "permissions"="VIEW,DELETE"
 *          },
 *          "activity"={
 *              "immutable"=true
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          }
 *      }
 * )
 */
class NotificationAlert implements ExtendEntityInterface
{
    use CreatedAtAwareTrait;
    use UpdatedAtAwareTrait;
    use ExtendEntityTrait;

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="alert_type", type="string", length=20, nullable=true)
     */
    private $alertType;

    /**
     * @var string
     *
     * @ORM\Column(name="source_type", type="string", length=50)
     */
    private $sourceType;

    /**
     * @var string
     *
     * @ORM\Column(name="resource_type", type="string", length=255)
     */
    private $resourceType;

    /**
     * @var string|null
     *
     * @ORM\Column(name="operation", type="string", length=50, nullable=true)
     */
    private $operation;

    /**
     * @var string|null
     *
     * @ORM\Column(name="step", type="string", length=50, nullable=true)
     */
    private $step;

    /**
     * @var int|null
     *
     * @ORM\Column(name="item_id", type="integer", nullable=true, length=255)
     */
    private $itemId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="external_id", type="string", nullable=true, length=255)
     */
    private $externalId;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_resolved", type="boolean", nullable=false, options={"default"=false})
     */
    private $resolved = false;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private $user;

    /**
     * @var string|null
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    private $message;

    /**
     * @var OrganizationInterface
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $organization;

    /**
     * @var array
     *
     * @ORM\Column(name="additional_info", type="json", nullable=true)
     */
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

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        if (!$this->updatedAt) {
            $this->updatedAt = clone $this->createdAt;
        }
    }
}
