<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\WorkflowBundle\Exception\SerializerException;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;

use Symfony\Component\Serializer\SerializerInterface;

/**
 * @ORM\Table(
 *      "oro_process_job",
 *      indexes={
 *          @ORM\Index(name="process_job_entity_hash_idx", columns={"entity_hash"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessJobRepository")
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
     * @var int
     *
     * @ORM\Column(name="entity_id", type="integer", nullable=true)
     */
    protected $entityId = null;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_hash", type="string", length=255, nullable=true)
     */
    protected $entityHash = null;

    /**
     * @var string
     *
     * @ORM\Column(name="serialized_data", type="text")
     */
    protected $serializedData;

    /**
     * @var ProcessData
     */
    protected $data;

    /**
     * @var SerializerInterface;
     */
    protected $serializer;

    /**
     * @var string
     */
    protected $serializeFormat;

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
     * @param int $entityId
     * @return ProcessJob
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        $trigger = $this->getProcessTrigger();
        if (!$trigger) {
            throw new \LogicException('Process trigger must be defined for process job');
        }

        $definition = $trigger->getDefinition();
        if (!$definition) {
            throw new \LogicException('Process definition must be defined for process job');
        }

        if (null !== $entityId) {
            $this->entityHash = self::generateEntityHash($definition->getRelatedEntity(), $entityId);
        } else {
            $this->entityHash = null;
        }

        return $this;
    }

    /**
     * Get data
     *
     * @return ProcessData
     * @throws SerializerException If data cannot be deserialized
     */
    public function getData()
    {
        if (null === $this->data) {
            if (!$this->serializedData) {
                $this->data = new ProcessData();
            } elseif (!$this->serializer) {
                throw new SerializerException('Cannot deserialize data of process job. Serializer is not available.');
            } else {
                $this->data = $this->serializer->deserialize(
                    $this->serializedData,
                    'Oro\Bundle\WorkflowBundle\Model\ProcessData',
                    $this->serializeFormat,
                    array('processJob' => $this)
                );
            }
        }
        return $this->data;
    }

    /**
     * @param ProcessData $data
     * @return ProcessJob
     */
    public function setData(ProcessData $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set serializer with custom format.
     *
     * This method should be called only from ProcessDataSerializeListener.
     *
     * @param SerializerInterface $serializer
     * @param string $format
     */
    public function setSerializer(SerializerInterface $serializer, $format)
    {
        $this->serializer      = $serializer;
        $this->serializeFormat = $format;
    }

    /**
     * @param string $entityClass
     * @param int $entityId
     * @return string
     */
    public static function generateEntityHash($entityClass, $entityId)
    {
        return sprintf('%s:%s', $entityClass, $entityId);
    }
}
