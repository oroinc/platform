<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Stub;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Job
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    protected $id;

    /**
     * @ORM\ManyToMany(targetEntity = "Job", fetch = "EAGER")
     * @ORM\JoinTable(name="jms_job_dependencies",
     *     joinColumns = { @ORM\JoinColumn(name = "source_job_id", referencedColumnName = "id") },
     *     inverseJoinColumns = { @ORM\JoinColumn(name = "dest_job_id", referencedColumnName = "id")}
     * )
     */
    protected $dependencies;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $closedAt;

    /**
     * @ORM\Column(type="string")
     */
    protected $state;


    public function __construct()
    {
    }
}
