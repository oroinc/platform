<?php
namespace Oro\Component\MessageQueue\Job;

class Job
{
    const STATUS_NEW = 'oro.message_queue_job.status.new';
    const STATUS_RUNNING = 'oro.message_queue_job.status.running';
    const STATUS_SUCCESS = 'oro.message_queue_job.status.success';
    const STATUS_FAILED = 'oro.message_queue_job.status.failed';
    const STATUS_CANCELLED = 'oro.message_queue_job.status.cancelled';

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var bool
     */
    protected $interrupted;

    /**
     * @var bool;
     */
    protected $unique;

    /**
     * @var Job
     */
    protected $rootJob;

    /**
     * @var Job[]
     */
    protected $childJobs;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime
     */
    protected $startedAt;

    /**
     * @var \DateTime
     */
    protected $stoppedAt;

    public function __construct()
    {
        $this->interrupted = false;
        $this->unique = false;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
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
    public function getName()
    {
        return $this->name;
    }

    /**
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
     * @internal
     *
     * @param Job $rootJob
     */
    public function setRootJob($rootJob)
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
     * @internal
     *
     * @param \DateTime $createdAt
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
     * @internal
     *
     * @param \DateTime $startedAt
     */
    public function setStartedAt(\DateTime $startedAt)
    {
        $this->startedAt = $startedAt;
    }

    /**
     * @return \DateTime
     */
    public function getStoppedAt()
    {
        return $this->stoppedAt;
    }

    /**
     * @internal
     *
     * @param \DateTime $stoppedAt
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
     * @internal
     *
     * @param Job[] $childJobs
     */
    public function setChildJobs(array $childJobs)
    {
        $this->childJobs = $childJobs;
    }
}
