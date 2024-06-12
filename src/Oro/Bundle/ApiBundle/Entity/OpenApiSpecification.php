<?php

namespace Oro\Bundle\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Represents OpenAPI specification.
 * @ORM\Entity()
 * @ORM\Table(name="oro_api_openapi_specification")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *     routeName="oro_openapi_specification_index",
 *     routeView="oro_openapi_specification_view",
 *     defaultValues={
 *         "ownership"={
 *             "owner_type"="USER",
 *             "owner_field_name"="owner",
 *             "owner_column_name"="user_owner_id",
 *             "organization_field_name"="organization",
 *             "organization_column_name"="organization_id"
 *         },
 *         "security"={
 *             "type"="ACL",
 *             "group_name"="",
 *             "category"="",
 *             "permissions"="VIEW;CREATE;EDIT;DELETE"
 *         }
 *     }
 * )
 */
class OpenApiSpecification
{
    public const STATUS_CREATING = 'creating';
    public const STATUS_CREATED = 'created';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RENEWING = 'renewing';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(name="status", type="string", length=8)
     */
    private ?string $status = null;

    /**
     * @ORM\Column(name="published", type="boolean")
     */
    private bool $published = false;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private ?User $owner = null;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private ?Organization $organization = null;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    private ?\DateTimeInterface $createdAt = null;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     */
    private ?string $name = null;

    /**
     * @ORM\Column(name="public_slug", type="string", length=100, nullable=true)
     */
    private ?string $publicSlug = null;

    /**
     * @ORM\Column(name="view", type="string", length=100)
     */
    private ?string $view = null;

    /**
     * @ORM\Column(name="format", type="string", length=20)
     */
    private ?string $format = null;

    /**
     * @ORM\Column(name="entities", type="simple_array", nullable=true)
     */
    private ?array $entities = null;

    /**
     * @ORM\Column(name="server_urls", type="simple_array", nullable=true)
     */
    private ?array $serverUrls = null;

    /**
     * @ORM\Column(name="specification", type="text", nullable=true)
     */
    private ?string $specification = null;

    /**
     * @ORM\Column(name="specification_created_at", type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $specificationCreatedAt = null;

    /**
     * Gets an unique identifier of the entity.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gets the processing status of the OpenAPI specification.
     * See STATUS_* constants.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Sets the processing status of the OpenAPI specification.
     * See STATUS_* constants.
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Gets a flag indicates whether the OpenAPI specification has been already published.
     */
    public function isPublished(): bool
    {
        return $this->published;
    }

    /**
     * Sets a flag indicates whether the OpenAPI specification has been already published.
     */
    public function setPublished(bool $published): self
    {
        $this->published = $published;

        return $this;
    }

    /**
     * Gets a user who requested the OpenAPI specification.
     */
    public function getOwner(): ?User
    {
        return $this->owner;
    }

    /**
     * Sets a user who requested the OpenAPI specification.
     */
    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Gets an organization the OpenAPI specification belongs to.
     */
    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    /**
     * Sets an organization the OpenAPI specification belongs to.
     */
    public function setOrganization(?Organization $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Gets the date and time when the entity was created.
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Gets the date and time when the entity was last updated.
     */
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * Gets the name of the OpenAPI specification.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Sets the name of the OpenAPI specification.
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets the URL slug for downloading the OpenAPI specification without authorization.
     */
    public function getPublicSlug(): ?string
    {
        return $this->publicSlug;
    }

    /**
     * Sets the URL slug for downloading the OpenAPI specification without authorization.
     */
    public function setPublicSlug(?string $publicSlug): self
    {
        $this->publicSlug = $publicSlug;

        return $this;
    }

    /**
     * Gets the API documentation view name for which the OpenAPI specification should be created.
     */
    public function getView(): ?string
    {
        return $this->view;
    }

    /**
     * Sets the API documentation view name for which the OpenAPI specification should be created.
     */
    public function setView(?string $view): self
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Gets the format in which the OpenAPI specification should be created.
     */
    public function getFormat(): ?string
    {
        return $this->format;
    }

    /**
     * Sets the format in which the OpenAPI specification should be created.
     */
    public function setFormat(?string $format): self
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Gets the list of entities for which the OpenAPI specification should be created.
     *
     * @return string[]|null
     */
    public function getEntities(): ?array
    {
        return $this->entities;
    }

    /**
     * Sets the list of entities for which the OpenAPI specification should be created.
     *
     * @param string[]|null $entities
     *
     * @return self
     */
    public function setEntities(?array $entities): self
    {
        if (null !== $entities && !$entities) {
            $entities = null;
        }
        $this->entities = $entities;

        return $this;
    }

    /**
     * Gets the list of server URLs that should be added to the OpenAPI specification.
     *
     * @return string[]|null
     */
    public function getServerUrls(): ?array
    {
        return $this->serverUrls;
    }

    /**
     * Sets the list of server URLs that should be added to the OpenAPI specification.
     *
     * @param string[]|null $serverUrls
     *
     * @return self
     */
    public function setServerUrls(?array $serverUrls): self
    {
        if (null !== $serverUrls && !$serverUrls) {
            $serverUrls = null;
        }
        $this->serverUrls = $serverUrls;

        return $this;
    }

    /**
     * Gets the created OpenAPI specification.
     */
    public function getSpecification(): ?string
    {
        return $this->specification;
    }

    /**
     * Sets the created OpenAPI specification.
     */
    public function setSpecification(?string $specification): self
    {
        $this->specification = $specification;

        return $this;
    }

    /**
     * Gets the date and time when the OpenAPI specification was created or renewed.
     */
    public function getSpecificationCreatedAt(): ?\DateTimeInterface
    {
        return $this->specificationCreatedAt;
    }

    /**
     * Sets the date and time when the OpenAPI specification was created or renewed.
     */
    public function setSpecificationCreatedAt(?\DateTimeInterface $specificationCreatedAt): self
    {
        $this->specificationCreatedAt = $specificationCreatedAt;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function beforeSave(): void
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;
        $this->status = self::STATUS_CREATING;
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
