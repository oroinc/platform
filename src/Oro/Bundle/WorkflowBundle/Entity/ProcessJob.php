<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessJobRepository;
use Oro\Bundle\WorkflowBundle\Exception\SerializerException;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Symfony\Component\Serializer\SerializerInterface;

/**
* Entity that represents Process Job
*
*/
#[ORM\Entity(repositoryClass: ProcessJobRepository::class)]
#[ORM\Table('oro_process_job')]
#[ORM\Index(columns: ['entity_hash'], name: 'process_job_entity_hash_idx')]
class ProcessJob
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ProcessTrigger::class)]
    #[ORM\JoinColumn(name: 'process_trigger_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?ProcessTrigger $processTrigger = null;

    #[ORM\Column(name: 'entity_id', type: Types::INTEGER, nullable: true)]
    protected ?int $entityId = null;

    #[ORM\Column(name: 'entity_hash', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $entityHash = null;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'serialized_data', type: Types::TEXT, nullable: true)]
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
     * @return int
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @param int $entityId
     * @return ProcessJob
     * @throws \LogicException
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
                    ProcessData::class,
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
