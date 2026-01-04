<?php

namespace Oro\Component\MessageQueue\Job;

/**
 * The Job object.
 */
class Job
{
    public const STATUS_NEW = 'oro.message_queue_job.status.new';
    public const STATUS_RUNNING = 'oro.message_queue_job.status.running';
    public const STATUS_SUCCESS = 'oro.message_queue_job.status.success';
    public const STATUS_FAILED = 'oro.message_queue_job.status.failed';
    public const STATUS_FAILED_REDELIVERED = 'oro.message_queue_job.status.failed_redelivered';
    public const STATUS_CANCELLED = 'oro.message_queue_job.status.cancelled';
    public const STATUS_STALE = 'oro.message_queue_job.status.stale';

    protected ?int $id = null;

    protected ?string $ownerId = null;

    protected ?string $name = null;

    protected ?string $status = null;

    protected ?bool $interrupted = null;

    protected ?bool $unique = null;

    /**
     * @var Job|null
     */
    protected $rootJob;

    /**
     * @var Job[]
     */
    protected $childJobs;

    protected ?\DateTimeInterface $createdAt = null;

    protected ?\DateTimeInterface $startedAt = null;

    protected ?\DateTimeInterface $lastActiveAt = null;

    protected ?\DateTimeInterface $stoppedAt = null;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var float
     */
    protected $jobProgress;

    public function __construct()
    {
        $this->interrupted = false;
        $this->unique = false;
        $this->data = [];
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Only JobProcessor is responsible to call this method.
     * Do not call from the outside.
     *
     * @internal
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }

    /**
     * Only JobProcessor is responsible to call this method.
     * Do not call from the outside.
     *
     * @internal
     *
     * @param string $ownerId
     */
    public function setOwnerId($ownerId)
    {
        $this->ownerId = $ownerId;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Only JobProcessor is responsible to call this method.
     * Do not call from the outside.
     *
     * @internal
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Only JobProcessor is responsible to call this method.
     * Do not call from the outside.
     *
     * @internal
     *
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return boolean
     */
    public function isInterrupted()
    {
        return $this->interrupted;
    }

    /**
     * Only JobProcessor is responsible to call this method.
     * Do not call from the outside.
     *
     * @internal
     *
     * @param boolean $interrupted
     */
    public function setInterrupted($interrupted)
    {
        $this->interrupted = $interrupted;
    }

    /**
     * @return boolean
     */
    public function isUnique()
    {
        return $this->unique;
    }

    /**
     * Only JobProcessor is responsible to call this method.
     * Do not call from the outside.
     *
     * @internal
     *
     * @param boolean $unique
     */
    public function setUnique($unique)
    {
        $this->unique = $unique;
    }

    /**
     * @return Job
     */
    public function getRootJob()
    {
        return $this->rootJob;
    }

    /**
     * Only JobProcessor is responsible to call this method.
     * Do not call from the outside.
     *
     * @internal
     */
    public function setRootJob(Job $rootJob)
    {
        $this->rootJob = $rootJob;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Only JobProcessor is responsible to call this method.
     * Do not call from the outside.
     *
     * @internal
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getStartedAt()
    {
        return $this->startedAt;
    }

    /**
     * Only JobProcessor is responsible to call this method.
     * Do not call from the outside.
     *
     * @internal
     */
    public function setStartedAt(\DateTime $startedAt)
    {
        $this->startedAt = $startedAt;
    }

    /**
     * @return \DateTime
     */
    public function getLastActiveAt()
    {
        return $this->lastActiveAt;
    }

    /**
     * @param \DateTime $lastActiveAt
     *
     * @return $this
     */
    public function setLastActiveAt(\DateTime $lastActiveAt)
    {
        $this->lastActiveAt = $lastActiveAt;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStoppedAt()
    {
        return $this->stoppedAt;
    }

    /**
     * Only JobProcessor is responsible to call this method.
     * Do not call from the outside.
     *
     * @internal
     */
    public function setStoppedAt(\DateTime $stoppedAt)
    {
        $this->stoppedAt = $stoppedAt;
    }

    /**
     * @return bool
     */
    public function isRoot()
    {
        return null === $this->getRootJob();
    }

    /**
     * @return Job[]
     */
    public function getChildJobs()
    {
        return $this->childJobs;
    }

    /**
     * Only JobProcessor is responsible to call this method.
     * Do not call from the outside.
     *
     * @internal
     *
     * @param Job[] $childJobs
     */
    public function setChildJobs($childJobs)
    {
        $this->childJobs = $childJobs;
    }

    public function addChildJob(Job $childJob)
    {
        $this->childJobs[] = $childJob;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    public function setData(array $data)
    {
        if (array_key_exists('_properties', $this->data)) {
            $data['_properties'] = $this->data['_properties'];
        }
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        if (!array_key_exists('_properties', $this->data)) {
            return [];
        }

        return $this->data['_properties'];
    }

    public function setProperties(array $properties)
    {
        $this->data['_properties'] = $properties;
    }

    /**
     * @return float
     */
    public function getJobProgress()
    {
        return $this->jobProgress;
    }

    /**
     * @param float $jobProgress
     */
    public function setJobProgress($jobProgress)
    {
        $this->jobProgress = $jobProgress;
    }
}
