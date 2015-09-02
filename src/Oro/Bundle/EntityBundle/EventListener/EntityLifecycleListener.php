<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\EntityBundle\Model\Lifecycle\LifecycleCreatedatInterface;
use Oro\Bundle\EntityBundle\Model\Lifecycle\LifecycleUpdatedatInterface;
use Oro\Bundle\EntityBundle\Model\Lifecycle\LifecycleUpdatedbyInterface;
use Oro\Bundle\EntityBundle\Model\Lifecycle\LifecycleOwnerInterface;

class EntityLifecycleListener
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
        $this->modifyOwner($entity);
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
        if ($entity instanceof LifecycleCreatedatInterface && !$entity->getCreatedAt()) {
            $entity->setCreatedAt($this->getNowDate());
        }
    }

    /**
     * @param object $entity
     */
    protected function modifyUpdatedAt($entity)
    {
        if ($entity instanceof LifecycleUpdatedatInterface && !$entity->isUpdatedUpdatedAtProperty()) {
            $entity->setUpdatedAt($this->getNowDate());
        }
    }

    /**
     * @param object $entity
     */
    protected function modifyOwner($entity)
    {
        if ($entity instanceof LifecycleOwnerInterface && !$entity->getOwner()) {
            $entity->setOwner($this->getUser());
        }
    }

    /**
     * @param object $entity
     */
    protected function modifyUpdatedBy($entity)
    {
        if ($entity instanceof LifecycleUpdatedbyInterface && !$entity->isUpdatedUpdatedByProperty()) {
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
