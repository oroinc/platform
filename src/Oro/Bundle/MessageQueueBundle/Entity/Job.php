<?php

namespace Oro\Bundle\MessageQueueBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository;
use Oro\Component\MessageQueue\Job\Job as BaseJob;

/**
* Message Queue Job entity class.
*/
#[ORM\Entity(repositoryClass: JobRepository::class)]
#[ORM\Table(name: 'oro_message_queue_job')]
#[Index(columns: ['status'], name: 'idx_status')]
class Job extends BaseJob
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'owner_id', type: Types::STRING, nullable: true)]
    protected ?string $ownerId = null;

    #[ORM\Column(name: 'name', type: Types::STRING, nullable: false)]
    protected ?string $name = null;

    #[ORM\Column(name: 'status', type: Types::STRING, nullable: false)]
    protected ?string $status = null;

    #[ORM\Column(name: 'interrupted', type: Types::BOOLEAN)]
    protected ?bool $interrupted = null;

    #[ORM\Column(name: '`unique`', type: Types::BOOLEAN)]
    protected ?bool $unique = null;

    /**
     * @var Job|null
     */
    #[ORM\ManyToOne(targetEntity: Job::class, inversedBy: 'childJobs')]
    #[ORM\JoinColumn(name: 'root_job_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected $rootJob;

    /**
     * @var Collection<int, Job>
     */
    #[ORM\OneToMany(mappedBy: 'rootJob', targetEntity: Job::class, cascade: ['persist'])]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    protected $childJobs;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, nullable: false)]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'started_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $startedAt = null;

    #[ORM\Column(name: 'last_active_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $lastActiveAt = null;

    #[ORM\Column(name: 'stopped_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $stoppedAt = null;

    /**
     * @var array
     */
    #[ORM\Column(name: 'data', type: 'json_array', nullable: true, options: ['jsonb' => true])]
    protected $data;

    /**
     * @var float
     */
    #[ORM\Column(name: 'job_progress', type: 'percent', nullable: true)]
    protected $jobProgress;

    public function __construct()
    {
        parent::__construct();

        $this->childJobs = new ArrayCollection();
    }
}
