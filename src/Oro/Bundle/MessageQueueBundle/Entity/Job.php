<?php
namespace Oro\Bundle\MessageQueueBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Component\MessageQueue\Job\JobEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="oro_message_queue_job")
 */
class Job extends JobEntity
{
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
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="unique_name", type="string", unique=true, nullable=true)
     */
    private $uniqueName;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", nullable=false)
     */
    private $status;

    /**
     * @var bool
     *
     * @ORM\Column(name="interrupted", type="boolean")
     */
    private $interrupted;

    /**
     * @var bool;
     *
     * @ORM\Column(name="unique", type="boolean")
     */
    private $unique;

    /**
     * @var Job
     *
     * @ORM\ManyToOne(targetEntity="Job", inversedBy="childJobs")
     * @ORM\JoinColumn(name="root_job_id", referencedColumnName="id")
     */
    private $rootJob;

    /**
     * @var Job[]
     *
     * @ORM\OneToMany(targetEntity="Job", mappedBy="rootJob")
     */
    private $childJobs;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="started_at", type="datetime", nullable=true)
     */
    private $startedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="stopped_at", type="datetime", nullable=true)
     */
    private $stoppedAt;

    public function __construct()
    {
        parent::__construct();

        $this->childJobs = new ArrayCollection();
    }
}
