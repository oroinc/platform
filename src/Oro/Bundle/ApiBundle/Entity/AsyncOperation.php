<?php

namespace Oro\Bundle\ApiBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * An entity to store details of asynchronous operations.
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_api_async_operation')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'ownership' => [
            'owner_type'               => 'USER',
            'owner_field_name'         => 'owner',
            'owner_column_name'        => 'user_owner_id',
            'organization_field_name'  => 'organization',
            'organization_column_name' => 'organization_id'
        ],
        'security'  => ['type' => 'ACL', 'group_name' => '', 'category' => '', 'permissions' => 'VIEW;CREATE']
    ]
)]
class AsyncOperation
{
    public const STATUS_NEW = 'new';
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 10)]
    private ?string $status = null;

    #[ORM\Column(name: 'progress', type: 'percent', nullable: true)]
    private ?float $progress = null;

    #[ORM\Column(name: 'job_id', type: Types::INTEGER, nullable: true)]
    private ?int $jobId = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?User $owner = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Organization $organization = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(name: 'elapsed_time', type: Types::INTEGER)]
    private ?int $elapsedTime = null;

    #[ORM\Column(name: 'data_file_name', type: Types::STRING, length: 50)]
    private ?string $dataFileName = null;

    #[ORM\Column(name: 'entity_class', type: Types::STRING, length: 255)]
    private ?string $entityClass = null;

    #[ORM\Column(name: 'action_name', type: Types::STRING, length: 20)]
    private ?string $actionName = null;

    #[ORM\Column(name: 'has_errors', type: Types::BOOLEAN, options: ['default' => false])]
    private ?bool $hasErrors = false;

    #[ORM\Column(name: 'summary', type: 'json_array', nullable: true)]
    private ?array $summary = null;

    #[ORM\Column(name: 'affected_entities', type: Types::JSON, nullable: true)]
    private ?array $affectedEntities = null;

    /**
     * Gets an unique identifier of the entity.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Gets the status of the asynchronous operation.
     * See STATUS_* constants.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Sets the status of the asynchronous operation.
     * See STATUS_* constants.
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Gets the progress, in percentage, for the asynchronous operation.
     */
    public function getProgress(): ?float
    {
        return $this->progress;
    }

    /**
     * Sets the progress, in percentage, for the asynchronous operation.
     */
    public function setProgress(?float $progress): self
    {
        $this->progress = $progress;

        return $this;
    }

    /**
     * Gets the identifier of a job that is used to process the asynchronous operation.
     */
    public function getJobId(): ?int
    {
        return $this->jobId;
    }

    /**
     * Sets the identifier of a job that is used to process the asynchronous operation.
     */
    public function setJobId(?int $jobId): self
    {
        $this->jobId = $jobId;

        return $this;
    }

    /**
     * Gets the date and time when the asynchronous operation was created.
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Gets the date and time when the asynchronous operation was last updated.
     */
    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * Gets the number of seconds the asynchronous operation has been running.
     */
    public function getElapsedTime(): int
    {
        return $this->elapsedTime;
    }

    /**
     * Gets a user who created the asynchronous operation.
     */
    public function getOwner(): ?User
    {
        return $this->owner;
    }

    /**
     * Sets a user who created the asynchronous operation.
     */
    public function setOwner(?User $owningUser): self
    {
        $this->owner = $owningUser;

        return $this;
    }

    /**
     * Gets an organization the asynchronous operation belongs to.
     */
    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    /**
     * Sets an organization the asynchronous operation belongs to.
     */
    public function setOrganization(?Organization $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Gets the name of a file contains the request data for the asynchronous operation.
     */
    public function getDataFileName(): ?string
    {
        return $this->dataFileName;
    }

    /**
     * Sets the name of a file contains the request data for the asynchronous operation.
     */
    public function setDataFileName(?string $dataFileName): self
    {
        $this->dataFileName = $dataFileName;

        return $this;
    }

    /**
     * Gets the class name of an entity for which the asynchronous operation was created.
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * Sets the class name of an entity for which the asynchronous operation was created.
     */
    public function setEntityClass(string $entityClass): self
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * Gets the name of an API action for which the asynchronous operation was created.
     */
    public function getActionName(): string
    {
        return $this->actionName;
    }

    /**
     * Sets the name of an API action for which the asynchronous operation was created.
     */
    public function setActionName(string $actionName): self
    {
        $this->actionName = $actionName;

        return $this;
    }

    /**
     * Indicates whether the asynchronous operation has at least one error.
     */
    public function isHasErrors(): bool
    {
        return $this->hasErrors;
    }

    /**
     * Sets a value indicates whether the asynchronous operation has at least one error.
     */
    public function setHasErrors(bool $hasErrors): void
    {
        $this->hasErrors = $hasErrors;
    }

    /**
     * Gets the summary statistics of the asynchronous operation.
     */
    public function getSummary(): ?array
    {
        return $this->summary;
    }

    /**
     * Sets the summary statistics of the asynchronous operation.
     */
    public function setSummary(array $summary)
    {
        $this->summary = $summary;
    }

    /**
     * Gets entities affected by the asynchronous operation.
     *
     * @return array|null [
     *                      'primary' => [[id, request id, is existing], ...],
     *                      'included' => [[class, id, request id, is existing], ...]
     *                    ]
     */
    public function getAffectedEntities(): ?array
    {
        return $this->affectedEntities;
    }

    /**
     * Sets entities affected by the asynchronous operation.
     */
    public function setAffectedEntities(?array $affectedEntities): void
    {
        $this->affectedEntities = $affectedEntities;
    }

    #[ORM\PrePersist]
    public function beforeSave(): void
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;
        $this->elapsedTime = 0;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->elapsedTime = $this->updatedAt->getTimestamp() - $this->createdAt->getTimestamp();
    }
}
