<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedByAwareInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ModifyCreatedAndUpdatedPropertiesListener implements OptionalListenerInterface
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var bool */
    protected $enabled = true;

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

        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $isCreatedAtUpdated = $this->updateCreatedAt($entity);
            $isUpdatedPropertiesUpdated = $this->setUpdatedProperties($entity);
            if ($isCreatedAtUpdated || $isUpdatedPropertiesUpdated) {
                $this->updateChangeSets($entity, $em, $uow);
            }
        }
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($this->setUpdatedProperties($entity)) {
                $this->updateChangeSets($entity, $em, $uow);
            }
        }
    }

    /**
     * @param object        $entity
     * @param EntityManager $em
     * @param UnitOfWork    $uow
     */
    protected function updateChangeSets($entity, EntityManager $em, UnitOfWork $uow)
    {
        $uow->recomputeSingleEntityChangeSet(
            $em->getClassMetadata(ClassUtils::getClass($entity)),
            $entity
        );
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
        if ($entity instanceof UpdatedByAwareInterface && !$entity->isUpdatedBySet()) {
            $user = $this->getUser();
            if (null !== $user) {
                $entity->setUpdatedBy($user);

                return true;
            }
        }

        return false;
    }

    /**
     * @return User|null
     */
    protected function getUser()
    {
        $token = $this->tokenStorage->getToken();
        if (null !== $token) {
            $user = $token->getUser();
            if ($user instanceof User) {
                return $user;
            }
        }

        return null;
    }

    /**
     * @return \DateTime
     */
    protected function getNowDate()
    {
        return new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
