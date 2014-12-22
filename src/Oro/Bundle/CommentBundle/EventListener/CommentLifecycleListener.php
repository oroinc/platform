<?php

namespace Oro\Bundle\CommentBundle\EventListener;

use Symfony\Component\Security\Core\User\UserInterface;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\CommentBundle\Entity\Comment;

class CommentLifecycleListener
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
    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if (!$this->isCommentEntity($entity)) {
            return;
        }

        /** @var Comment $entity */
        $this->setUpdatedProperties($entity, $args->getEntityManager(), true);
    }

    /**
     * @param Comment       $comment
     * @param EntityManager $entityManager
     * @param bool          $update
     */
    protected function setUpdatedProperties(Comment $comment, EntityManager $entityManager, $update = false)
    {
        $newUpdatedBy = $this->getUser($entityManager);
        $unitOfWork   = $entityManager->getUnitOfWork();

        if ($update && $newUpdatedBy != $comment->getUpdatedBy()) {
            $unitOfWork->propertyChanged($comment, 'updatedBy', $comment->getUpdatedBy(), $newUpdatedBy);
        }

        $comment->setUpdatedBy($newUpdatedBy);
    }

    /**
     * @param mixed $entity
     *
     * @return bool
     */
    protected function isCommentEntity($entity)
    {
        return $entity instanceof Comment;
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
