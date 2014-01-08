<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use JMS\JobQueueBundle\Entity\Job;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("oro_process_job")
 * @ORM\Entity
 */
class ProcessJob
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Job
     *
     * @ORM\OneToOne(targetEntity="JMS\JobQueueBundle\Entity\Job")
     * @ORM\JoinColumn(name="job_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $job;

    /**
     * @var ProcessTrigger
     *
     * @ORM\ManyToOne(targetEntity="ProcessTrigger")
     * @ORM\JoinColumn(name="process_trigger_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $processTrigger;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_hash", type="string", length=255)
     */
    protected $entityHash;

    /**
     * @var string
     *
     * @ORM\Column(name="serialized_data", type="text")
     */
    protected $serializedData;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Job $job
     * @return ProcessJob
     */
    public function setJob($job)
    {
        $this->job = $job;

        return $this;
    }

    /**
     * @return Job
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @param ProcessTrigger $processTrigger
     * @return ProcessJob
     */
    public function setProcessTrigger($processTrigger)
    {
        $this->processTrigger = $processTrigger;

        return $this;
    }

    /**
     * @return ProcessTrigger
     */
    public function getProcessTrigger()
    {
        return $this->processTrigger;
    }

    /**
     * @param string $entityHash
     * @return ProcessJob
     */
    public function setEntityHash($entityHash)
    {
        $this->entityHash = $entityHash;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntityHash()
    {
        return $this->entityHash;
    }

    /**
     * @param string $serializedData
     * @return ProcessJob
     */
    public function setSerializedData($serializedData)
    {
        $this->serializedData = $serializedData;

        return $this;
    }

    /**
     * @return string
     */
    public function getSerializedData()
    {
        return $this->serializedData;
    }
}
