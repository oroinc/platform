<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;

use Symfony\Component\Serializer\SerializerInterface;

class ProcessDataSerializeListener
{
    /**
     * @var string
     */
    protected $format = 'json';

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * Constructor
     *
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Deserialize data of ProcessJob
     *
     * @param ProcessJob $processJob
     */
    protected function deserialize(ProcessJob $processJob)
    {
        // Pass serializer into ProcessJob to make lazy loading of entity item data.
        $processJob->setSerializer($this->serializer, $this->format);
    }

    /**
     * @param object $entity
     * @return bool
     */
    protected function isSupported($entity)
    {
        return $entity instanceof ProcessJob;
    }

    /**
     * Before flush, serializes all ProcessJob's data
     *
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $unitOfWork = $args->getEntityManager()->getUnitOfWork();
        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if ($this->isSupported($entity)) {
                $this->serialize($entity, $unitOfWork);
            }
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($this->isSupported($entity)) {
                $this->serialize($entity, $unitOfWork);
            }
        }
    }

    /**
     * After ProcessJob loaded deserialize $serializedData
     *
     * @param LifecycleEventArgs $args
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        /** @var ProcessJob $entity */
        $entity = $args->getEntity();
        if ($this->isSupported($entity)) {
            $this->deserialize($entity);
        }
    }

    /**
     * Serialize data of ProcessJob
     *
     * @param ProcessJob $processJob
     * @param UnitOfWork $unitOfWork
     */
    protected function serialize(ProcessJob $processJob, UnitOfWork $unitOfWork)
    {
        $oldSerializedData = $processJob->getSerializedData();
        $oldEntityId = $processJob->getEntityId();
        $oldEntityHash = $processJob->getEntityHash();

        $newSerializedData = $this->serializer->serialize(
            $processJob->getData(),
            $this->format,
            array('processJob' => $processJob)
        );
        $newEntityId = $processJob->getEntityId();
        $newEntityHash = $processJob->getEntityHash();

        $processJob->setSerializedData($newSerializedData);

        if ($newSerializedData != $oldSerializedData) {
            $unitOfWork->propertyChanged($processJob, 'serializedData', $oldSerializedData, $newSerializedData);
        }
        if ($newEntityId != $oldEntityId) {
            $unitOfWork->propertyChanged($processJob, 'entityId', $oldEntityId, $newEntityId);
        }
        if ($newEntityHash != $oldEntityHash) {
            $unitOfWork->propertyChanged($processJob, 'entityHash', $oldEntityHash, $newEntityHash);
        }
    }
}
