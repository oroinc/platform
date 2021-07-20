<?php

namespace Oro\Bundle\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * An entity to store details of asynchronous operations.
 *
 * @ORM\Table(name="oro_api_async_operation")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      defaultValues={
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="",
 *              "category"="",
 *              "permissions"="VIEW;CREATE"
 *          }
 *      }
 * )
 */
class AsyncOperation
{
    public const STATUS_NEW       = 'new';
    public const STATUS_RUNNING   = 'running';
    public const STATUS_SUCCESS   = 'success';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=10)
     */
    private $status;

    /**
     * @var float|null
     *
     * @ORM\Column(name="progress", type="percent", nullable=true)
     */
    private $progress;

    /**
     * @var int|null
     *
     * @ORM\Column(name="job_id", type="integer", nullable=true)
     */
    private $jobId;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $owner;

    /**
     * @var Organization|null
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $organization;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
     * @var int
     *
     * @ORM\Column(name="elapsed_time", type="integer")
     */
    private $elapsedTime;

    /**
     * @var string
     *
     * @ORM\Column(name="data_file_name", type="string", length=50)
     */
    private $dataFileName;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_class", type="string", length=255)
     */
    private $entityClass;

    /**
     * @var string
     *
     * @ORM\Column(name="action_name", type="string", length=20)
     */
    private $actionName;

    /**
     * @var bool
     *
     * @ORM\Column(name="has_errors", type="boolean", options={"default"=false})
     */
    private $hasErrors = false;

    /**
     * @var array|null
     *
     * @ORM\Column(name="summary", type="json_array", nullable=true)
     */
    private $summary;

    /**
     * Gets an unique identifier of the entity.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the status of the asynchronous operation.
     * See STATUS_* constants.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets the status of the asynchronous operation.
     * See STATUS_* constants.
     *
     * @param string $status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Gets the progress, in percentage, for the asynchronous operation.
     *
     * @return float|null
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * Sets the progress, in percentage, for the asynchronous operation.
     *
     * @param float|null $progress
     *
     * @return $this
     */
    public function setProgress($progress)
    {
        $this->progress = $progress;

        return $this;
    }

    /**
     * Gets the identifier of a job that is used to process the asynchronous operation.
     *
     * @return int|null
     */
    public function getJobId()
    {
        return $this->jobId;
    }

    /**
     * Sets the identifier of a job that is used to process the asynchronous operation.
     *
     * @param int|null $jobId
     *
     * @return $this
     */
    public function setJobId($jobId)
    {
        $this->jobId = $jobId;

        return $this;
    }

    /**
     * Gets the date and time when the asynchronous operation was created.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Gets the date and time when the asynchronous operation was last updated.
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Gets the number of seconds the asynchronous operation has been running.
     *
     * @return int
     */
    public function getElapsedTime()
    {
        return $this->elapsedTime;
    }

    /**
     * Gets a user who created the asynchronous operation.
     *
     * @return User|null
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Sets a user who created the asynchronous operation.
     *
     * @param User $owningUser
     *
     * @return $this
     */
    public function setOwner(User $owningUser = null)
    {
        $this->owner = $owningUser;

        return $this;
    }

    /**
     * Gets an organization the asynchronous operation belongs to.
     *
     * @return Organization|null
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Sets an organization the asynchronous operation belongs to.
     *
     * @param Organization $organization
     *
     * @return $this
     */
    public function setOrganization(Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Gets the name of a file contains the request data for the asynchronous operation.
     *
     * @return string
     */
    public function getDataFileName()
    {
        return $this->dataFileName;
    }

    /**
     * Sets the name of a file contains the request data for the asynchronous operation.
     *
     * @param string $dataFileName
     *
     * @return $this
     */
    public function setDataFileName($dataFileName)
    {
        $this->dataFileName = $dataFileName;

        return $this;
    }

    /**
     * Gets the class name of an entity for which the asynchronous operation was created.
     *
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * Sets the class name of an entity for which the asynchronous operation was created.
     *
     * @param string $entityClass
     *
     * @return $this
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * Gets the name of an API action for which the asynchronous operation was created.
     *
     * @return string
     */
    public function getActionName()
    {
        return $this->actionName;
    }

    /**
     * Sets the name of an API action for which the asynchronous operation was created.
     *
     * @param string $actionName
     *
     * @return $this
     */
    public function setActionName($actionName)
    {
        $this->actionName = $actionName;

        return $this;
    }

    /**
     * Indicates whether the asynchronous operation has at least one error.
     *
     * @return bool
     */
    public function isHasErrors()
    {
        return $this->hasErrors;
    }

    /**
     * Sets a value indicates whether the asynchronous operation has at least one error.
     *
     * @param bool $hasErrors
     */
    public function setHasErrors($hasErrors)
    {
        $this->hasErrors = $hasErrors;
    }

    /**
     * Gets the summary statistics of the asynchronous operation.
     *
     * @return array|null
     */
    public function getSummary()
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
     * @ORM\PrePersist
     */
    public function beforeSave()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;
        $this->elapsedTime = 0;
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->elapsedTime = $this->updatedAt->getTimestamp() - $this->createdAt->getTimestamp();
    }
}
