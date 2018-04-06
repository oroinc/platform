<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\Serializer\SerializerInterface;

class ProcessDataSerializeListener
{
    /**
     * @var string
     */
    protected $format = 'json';

    /**
     * @var ProcessJob[]
     */
    protected $scheduledEntities = array();

    /** @var ServiceLink */
    private $serializerLink;

    /**
     * @param ServiceLink $serializerLink
     */
    public function __construct(ServiceLink $serializerLink)
    {
        $this->serializerLink = $serializerLink;
    }

    /**
     * Deserialize data of ProcessJob
     *
     * @param ProcessJob $processJob
     */
    protected function deserialize(ProcessJob $processJob)
    {
        // Pass serializer into ProcessJob to make lazy loading of entity item data.
        $processJob->setSerializer($this->getSerializer(), $this->format);
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
        /** @var ProcessJob $entity */
        $unitOfWork = $args->getEntityManager()->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if ($this->isSupported($entity) && $entity->getData()->isModified()) {
                $this->scheduledEntities[] = $entity;
            }
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($this->isSupported($entity) && $entity->getData()->isModified()) {
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
            while ($processJob = array_shift($this->scheduledEntities)) {
                $this->serialize($processJob);
            }
            $args->getEntityManager()->flush();
        }
    }

    /**
     * After ProcessJob loaded deserialize $serializedData
     *
     * @param ProcessJob         $entity
     * @param LifecycleEventArgs $args
     */
    public function postLoad(ProcessJob $entity, LifecycleEventArgs $args)
    {
        $this->deserialize($entity);
    }

    /**
     * Serialize data of ProcessJob
     *
     * @param ProcessJob $processJob
     */
    protected function serialize(ProcessJob $processJob)
    {
        $processData = $processJob->getData();
        $serializedData = $this->getSerializer()
            ->serialize($processData, $this->format, array('processJob' => $processJob));
        $processJob->setSerializedData($serializedData);
        $processData->setModified(false);
    }

    /**
     * @return SerializerInterface
     */
    private function getSerializer()
    {
        return $this->serializerLink->getService();
    }
}
