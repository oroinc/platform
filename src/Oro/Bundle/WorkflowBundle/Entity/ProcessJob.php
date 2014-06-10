<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Table("oro_process_job")
 * @ORM\Entity()
 * @Config()
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
     * @var array
     *
     * @ORM\Column(name="entity_id", type="integer")
     */
    protected $entityId;

    /**
     * @var Collection
     */
    protected $data;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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

    /**
     * @return array
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @param array $entityId
     * @return ProcessJob
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param Collection $data
     * @return ProcessJob
     */
    public function setData(Collection $data)
    {
        $this->data = $data;

        return $this;
    }
}
