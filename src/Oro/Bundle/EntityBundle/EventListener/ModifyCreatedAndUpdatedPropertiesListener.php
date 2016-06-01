<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedByAwareInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;

class ModifyCreatedAndUpdatedPropertiesListener implements OptionalListenerInterface
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var bool */
    protected $enabled = true;

    /**
     * @var ClassMetadata[]
     */
    protected $metadataCache = [];

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        $this->entityManager = $args->getEntityManager();
        $unitOfWork = $this->entityManager->getUnitOfWork();

        $newEntities = $unitOfWork->getScheduledEntityInsertions();
        $updateEntities = $unitOfWork->getScheduledEntityUpdates();

        foreach ($newEntities as $entity) {
            $isCreatedAtUpdated = $this->updateCreatedAt($entity);
            $isUpdatedPropertiesUpdated = $this->setUpdatedProperties($entity);
            if ($isCreatedAtUpdated || $isUpdatedPropertiesUpdated) {
                $this->updateChangeSets($entity);
            }
        }
        foreach ($updateEntities as $entity) {
            if ($this->setUpdatedProperties($entity)) {
                $this->updateChangeSets($entity);
            }
        }
    }

    /**
     * @param object $entity
     */
    protected function updateChangeSets($entity)
    {
        $metadata = $this->getMetadataForEntity($entity);
        $this->entityManager->getUnitOfWork()
            ->recomputeSingleEntityChangeSet($metadata, $entity);
    }

    /**
     * @param object $entity
     *
     * @return ClassMetadata
     */
    protected function getMetadataForEntity($entity)
    {
        $class = ClassUtils::getClass($entity);
        if (!isset($this->metadataCache[$class])) {
            $this->metadataCache[$class] = $this->entityManager->getClassMetadata($class);
        }

        return $this->metadataCache[$class];
    }

    /**
     * @param object $entity
     *
     * @return bool
     */
    protected function setUpdatedProperties($entity)
    {
        $isUpdatedAtUpdated = $this->updateUpdatedAt($entity);
        $isUpdatedByUpdated = $this->updateUpdatedBy($entity);

        return $isUpdatedAtUpdated || $isUpdatedByUpdated;
    }

    /**
     * @param object $entity
     *
     * @return bool
     */
    protected function updateCreatedAt($entity)
    {
        if ($entity instanceof CreatedAtAwareInterface && !$entity->getCreatedAt()) {
            $entity->setCreatedAt($this->getNowDate());

            return true;
        }

        return false;
    }

    /**
     * @param object $entity
     *
     * @return bool
     */
    protected function updateUpdatedAt($entity)
    {
        if ($entity instanceof UpdatedAtAwareInterface && !$entity->isUpdatedAtSet()) {
            $entity->setUpdatedAt($this->getNowDate());

            return true;
        }

        return false;
    }

    /**
     * @param object $entity
     *
     * @return bool
     */
    protected function updateUpdatedBy($entity)
    {
        $user = $this->getUser();
        if ($entity instanceof UpdatedByAwareInterface && !$entity->isUpdatedBySet() && $user instanceof User) {
            $entity->setUpdatedBy($user);

            return true;
        }

        return false;
    }

    /**
     * @return UserInterface|null
     */
    protected function getUser()
    {
        if (!$token = $this->tokenStorage->getToken()) {
            return null;
        }

        return $token->getUser();
    }

    /**
     * @return \DateTime
     */
    protected function getNowDate()
    {
        return new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
