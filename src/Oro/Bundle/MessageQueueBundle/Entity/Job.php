<?php

namespace Oro\Bundle\MessageQueueBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Oro\Component\MessageQueue\Job\Job as BaseJob;

/**
* Message Queue Job entity class.
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository")
 * @ORM\Table(
 *     name="oro_message_queue_job",
 *     indexes={
 *          @Index(name="idx_status", columns={"status"}),
 *          @Index(name="oro_message_queue_job_inx", columns={"root_job_id", "name", "owner_id"})
 *     }
 * )
 */
class Job extends BaseJob
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="owner_id", type="string", nullable=true)
     */
    protected $ownerId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", nullable=false)
     */
    protected $status;

    /**
     * @var bool
     *
     * @ORM\Column(name="interrupted", type="boolean")
     */
    protected $interrupted;

    /**
     * @var bool;
     *
     * @ORM\Column(name="`unique`", type="boolean")
     */
    protected $unique;

    /**
     * @var Job
     *
     * @ORM\ManyToOne(targetEntity="Job", inversedBy="childJobs")
     * @ORM\JoinColumn(name="root_job_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $rootJob;

    /**
     * @var Job[]
     *
     * @ORM\OneToMany(targetEntity="Job", mappedBy="rootJob", cascade={"persist"})
     */
    protected $childJobs;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="started_at", type="datetime", nullable=true)
     */
    protected $startedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_active_at", type="datetime", nullable=true)
     */
    protected $lastActiveAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="stopped_at", type="datetime", nullable=true)
     */
    protected $stoppedAt;

    /**
     * @var array
     *
     * @ORM\Column(name="data", type="json_array", nullable=true, options={"jsonb"=true})
     */
    protected $data;

    /**
     * @var float
     *
     * @ORM\Column(name="job_progress", type="percent", nullable=true)
     */
    protected $jobProgress;

    public function __construct()
    {
        parent::__construct();

        $this->childJobs = new ArrayCollection();
    }
}
