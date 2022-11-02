<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Performs serialization and deserialization of ProcessJob data.
 */
class ProcessDataSerializeListener implements ServiceSubscriberInterface
{
    private ContainerInterface $container;
    private string $format = 'json';
    /** @var ProcessJob[] */
    private array $scheduledEntities = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Before flush, serializes all ProcessJob's data
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $uow = $args->getEntityManager()->getUnitOfWork();
        $this->scheduleEntities($uow->getScheduledEntityInsertions());
        $this->scheduleEntities($uow->getScheduledEntityUpdates());
    }

    public function postFlush(PostFlushEventArgs $args): void
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
     */
    public function postLoad(ProcessJob $entity): void
    {
        $this->deserialize($entity);
    }

    private function scheduleEntities(array $entities): void
    {
        foreach ($entities as $entity) {
            if ($this->isSupported($entity) && $entity->getData()->isModified()) {
                $this->scheduledEntities[] = $entity;
            }
        }
    }

    /**
     * Serialize data of ProcessJob
     */
    private function serialize(ProcessJob $processJob): void
    {
        $processData = $processJob->getData();
        $serializedData = $this->getSerializer()->serialize(
            $processData,
            $this->format,
            ['processJob' => $processJob]
        );
        $processJob->setSerializedData($serializedData);
        $processData->setModified(false);
    }

    /**
     * Deserialize data of ProcessJob
     */
    private function deserialize(ProcessJob $processJob): void
    {
        // Pass serializer into ProcessJob to make lazy loading of entity item data.
        $processJob->setSerializer($this->getSerializer(), $this->format);
    }

    private function isSupported(object $entity): bool
    {
        return $entity instanceof ProcessJob;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_workflow.serializer.process.serializer' => SerializerInterface::class
        ];
    }

    private function getSerializer(): SerializerInterface
    {
        return $this->container->get('oro_workflow.serializer.process.serializer');
    }
}
