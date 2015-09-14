<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\EntityBundle\Model\CreatedAtAwareInterface;
use Oro\Bundle\EntityBundle\Model\UpdatedAtAwareInterface;
use Oro\Bundle\EntityBundle\Model\UpdatedByAwareInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;

class ModifyCreatedAndUpdatedPropertiesListener implements OptionalListenerInterface
{
    /** @var ServiceLink */
    protected $securityFacadeLink;

    /** @var EntityManager */
    protected $entityManager;

    /**
     * @param ServiceLink $securityFacadeLink
     */
    public function __construct(ServiceLink $securityFacadeLink)
    {
        $this->securityFacadeLink = $securityFacadeLink;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $this->entityManager = $args->getEntityManager();
        $this->setCreatedProperties($entity);
        $this->setUpdatedProperties($entity);
    }

    /**
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();
        $this->entityManager = $args->getEntityManager();
        $this->setUpdatedProperties($entity);
    }

    /**
     * @param object $entity
     */
    protected function setCreatedProperties($entity)
    {
        $this->modifyCreatedAt($entity);
    }

    /**
     * @param object $entity
     */
    protected function setUpdatedProperties($entity)
    {
        $this->modifyUpdatedAt($entity);
        $this->modifyUpdatedBy($entity);
    }

    /**
     * @param object $entity
     */
    protected function modifyCreatedAt($entity)
    {
        if ($entity instanceof CreatedAtAwareInterface && !$entity->getCreatedAt()) {
            $entity->setCreatedAt($this->getNowDate());
        }
    }

    /**
     * @param object $entity
     */
    protected function modifyUpdatedAt($entity)
    {
        if ($entity instanceof UpdatedAtAwareInterface && !$entity->isUpdatedAtSetted()) {
            $entity->setUpdatedAt($this->getNowDate());
        }
    }

    /**
     * @param object $entity
     */
    protected function modifyUpdatedBy($entity)
    {
        if ($entity instanceof UpdatedByAwareInterface && !$entity->isUpdatedBySetted()) {
            $entity->setUpdatedBy($this->getUser());
        }
    }

    /**
     * @return User|null
     */
    protected function getUser()
    {
        /** @var User $user */
        $user = $this->securityFacadeLink->getService()->getLoggedUser();
        if ($user && $this->entityManager->getUnitOfWork()->getEntityState($user) === UnitOfWork::STATE_DETACHED) {
            $user = $this->entityManager->find('OroUserBundle:User', $user->getId());
        }

        return $user;
    }

    /**
     * @return \DateTime
     */
    protected function getNowDate()
    {
        return new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
