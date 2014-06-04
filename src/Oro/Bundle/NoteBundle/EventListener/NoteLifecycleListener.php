<?php

namespace Oro\Bundle\NoteBundle\EventListener;

use Symfony\Component\Security\Core\User\UserInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

class NoteLifecycleListener
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
     * @param Note          $note
     * @param EntityManager $entityManager
     * @param bool          $update
     */
    protected function setUpdatedProperties(Note $note, EntityManager $entityManager, $update = false)
    {
        $newUpdatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $newUpdatedBy = $this->getUser($entityManager);

        $unitOfWork = $entityManager->getUnitOfWork();
        if ($update && $newUpdatedBy != $note->getUpdatedBy()) {
            $unitOfWork->propertyChanged($note, 'updatedAt', $note->getUpdatedAt(), $newUpdatedAt);
            $unitOfWork->propertyChanged($note, 'updatedBy', $note->getUpdatedBy(), $newUpdatedBy);
        }

        $note->setUpdatedAt($newUpdatedAt);
        $note->setUpdatedBy($newUpdatedBy);
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
     * @return UserInterface|null
     */
    protected function getUser(EntityManager $entityManager)
    {
        $user = $this->securityFacadeLink->getService()->getLoggedUser();
        if ($user && $entityManager->getUnitOfWork()->getEntityState($user) == UnitOfWork::STATE_DETACHED) {
            $user = $entityManager->find('OroUserBundle:User', $user->getId());
        }

        return $user;
    }
}
