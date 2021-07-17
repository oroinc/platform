<?php

namespace Oro\Bundle\ImportExportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Entity holds information about import/export operations
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\ImportExportBundle\Entity\Repository\ImportExportResultRepository")
 * @ORM\Table(name="oro_import_export_result")
 * @Config(
 *     defaultValues={
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL"
 *          }
 *     }
 * )
 *
 * @ORM\HasLifecycleCallbacks()
 */
class ImportExportResult implements CreatedAtAwareInterface, OrganizationAwareInterface
{
    use CreatedAtAwareTrait;
    use OrganizationAwareTrait;

    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, name="filename", unique=true, nullable=true)
     */
    protected $filename;

    /**
     * @var integer
     *
     * @ORM\Column(name="job_id", type="integer", unique=true, nullable=false)
     */
    protected $jobId;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, name="type", unique=false, nullable=false)
     */
    protected $type;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, name="entity", unique=false, nullable=false)
     */
    protected $entity;

    /**
     * @var array
     *
     * @ORM\Column(name="options", type="array", nullable=true)
     */
    protected $options = [];

    /**
     * @var boolean
     *
     * @ORM\Column(name="expired", type="boolean", options={"default"=false})
     */
    protected $expired = false;

    /**
     * @return int
     */
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
     * @param string $filename
     *
     * @return $this
     */
    public function setFilename(string $filename = null): ImportExportResult
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * @return int
     */
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

    /**
     * @return bool
     */
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
