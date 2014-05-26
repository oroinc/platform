<?php

namespace Oro\Bundle\NoteBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

class NoteLifecycleSubscriber implements EventSubscriber
{
    /** @var ServiceLink */
    protected $securityFacadeLink;

    /**
     * @param ServiceLink $securityFacadeLink
     */
    public function __construct(ServiceLink $securityFacadeLink)
    {
        $this->securityFacadeLink = $securityFacadeLink;
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return ['prePersist', 'preUpdate'];
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if (!$this->isNoteEntity($entity)) {
            return;
        }

        /** @var Note $entity */
        $this->setCreatedProperties($entity, $args->getEntityManager());
        $this->setUpdatedProperties($entity, $args->getEntityManager());
    }

    /**
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();
        if (!$this->isNoteEntity($entity)) {
            return;
        }

        /** @var Note $entity */
        $this->setUpdatedProperties($entity, $args->getEntityManager(), true);
    }

    /**
     * @param Note          $note
     * @param EntityManager $entityManager
     */
    protected function setCreatedProperties(Note $note, EntityManager $entityManager)
    {
        $note->setCreatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $note->setUpdatedBy($this->getUser($entityManager));
    }

    /**
     * @param Note          $contact
     * @param EntityManager $entityManager
     * @param bool          $update
     */
    protected function setUpdatedProperties(Note $contact, EntityManager $entityManager, $update = false)
    {
        $newUpdatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $newUpdatedBy = $this->getUser($entityManager);

        $unitOfWork = $entityManager->getUnitOfWork();
        if ($update) {
            $unitOfWork->propertyChanged($contact, 'updatedAt', $contact->getUpdatedAt(), $newUpdatedAt);
            $unitOfWork->propertyChanged($contact, 'updatedBy', $contact->getUpdatedBy(), $newUpdatedBy);
        }

        $contact->setUpdatedAt($newUpdatedAt);
        $contact->setUpdatedBy($newUpdatedBy);
    }

    /**
     * @param mixed $entity
     *
     * @return bool
     */
    protected function isNoteEntity($entity)
    {
        return $entity instanceof Note;
    }

    /**
     * @param EntityManager $entityManager
     *
     * @return User|null
     */
    protected function getUser(EntityManager $entityManager)
    {
        $user = $this->securityFacadeLink->getService()->getLoggedUser();
        if (!$user) {
            return null;
        }

        if ($entityManager->getUnitOfWork()->getEntityState($user) == UnitOfWork::STATE_DETACHED) {
            $user = $entityManager->find('OroUserBundle:User', $user->getId());
        }

        return $user;
    }
}
