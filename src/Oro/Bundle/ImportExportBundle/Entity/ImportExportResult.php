<?php

namespace Oro\Bundle\ImportExportBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\ImportExportBundle\Entity\Repository\ImportExportResultRepository;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Entity holds information about import/export operations
 *
 *
 */
#[ORM\Entity(repositoryClass: ImportExportResultRepository::class)]
#[ORM\Table(name: 'oro_import_export_result')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'ownership' => [
            'owner_type' => 'USER',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id'
        ],
        'security' => ['type' => 'ACL']
    ]
)]
class ImportExportResult implements CreatedAtAwareInterface, OrganizationAwareInterface
{
    use CreatedAtAwareTrait;
    use OrganizationAwareTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?User $owner = null;

    #[ORM\Column(name: 'filename', type: Types::STRING, length: 255, unique: true, nullable: true)]
    protected ?string $filename = null;

    #[ORM\Column(name: 'job_id', type: Types::INTEGER, unique: true, nullable: false)]
    protected ?int $jobId = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 255, unique: false, nullable: false)]
    protected ?string $type = null;

    #[ORM\Column(name: 'entity', type: Types::STRING, length: 255, unique: false, nullable: false)]
    protected ?string $entity = null;

    /**
     * @var array
     */
    #[ORM\Column(name: 'options', type: Types::ARRAY, nullable: true)]
    protected $options = [];

    #[ORM\Column(name: 'expired', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $expired = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    /**
     * @param User $owner
     *
     * @return $this
     */
    public function setOwner(User $owner): ImportExportResult
    {
        $this->owner = $owner;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * @param string|null $filename
     *
     * @return $this
     */
    public function setFilename(string $filename = null): ImportExportResult
    {
        $this->filename = $filename;

        return $this;
    }

    public function getJobId(): ?int
    {
        return $this->jobId;
    }

    /**
     * @param int $jobId
     *
     * @return $this
     */
    public function setJobId(int $jobId): ImportExportResult
    {
        $this->jobId = $jobId;

        return $this;
    }

    public function isExpired(): ?bool
    {
        return $this->expired;
    }

    /**
     * @param bool $expired
     *
     * @return $this
     */
    public function setExpired(bool $expired): ImportExportResult
    {
        $this->expired = $expired;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): ImportExportResult
    {
        $this->type = $type;

        return $this;
    }

    public function getEntity(): ?string
    {
        return $this->entity;
    }

    public function setEntity(string $entity): ImportExportResult
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options ?: [];
    }

    /**
     * @param array $options
     *
     * @return ImportExportResult
     */
    public function setOptions($options): ImportExportResult
    {
        $this->options = $options;

        return $this;
    }
}
