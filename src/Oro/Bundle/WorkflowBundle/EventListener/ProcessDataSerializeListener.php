<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
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
     * @var bool
     */
    protected $needFlush = false;

    /**
     * @var ProcessJob[]
     */
    protected $scheduledEntities;


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
                $this->scheduledEntities[] = $entity;
            }
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($this->isSupported($entity)) {
                $this->scheduledEntities[] = $entity;
            }
        }
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if ($this->scheduledEntities) {
            $this->needFlush = false;
            $entityManager   = $args->getEntityManager();
            $unitOfWork      = $entityManager->getUnitOfWork();

            while ($processJob = array_shift($this->scheduledEntities)) {
                $this->serialize($processJob, $unitOfWork);
            }

            if ($this->needFlush) {
                $entityManager->flush();
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
        $processData = $processJob->getData();

        if ($processData->isModified()) {
            $oldEntityHash     = $processJob->getEntityHash();
            $oldEntityId       = $processJob->getEntityId();
            $oldSerializedData = $processJob->getSerializedData();

            $newSerializedData = $this->serializer->serialize(
                $processData,
                $this->format,
                array('processJob' => $processJob)
            );

            $newEntityId   = $processJob->getEntityId();
            $newEntityHash = $processJob->getEntityHash();

            $processJob->setSerializedData($newSerializedData);
            $this->propertyChanged($unitOfWork, $processJob, 'serializedData', $oldSerializedData, $newSerializedData);
            $this->propertyChanged($unitOfWork, $processJob, 'entityId', $oldEntityId, $newEntityId);
            $this->propertyChanged($unitOfWork, $processJob, 'entityHash', $oldEntityHash, $newEntityHash);
            $processData->setModified(false);
        }
    }

    protected function propertyChanged($unitOfWork, $entity, $propertyName, $old, $new)
    {
        if ($new != $old) {
            $unitOfWork->propertyChanged($entity, $propertyName, $old, $new);
            $this->needFlush = true;
        }
    }
}
