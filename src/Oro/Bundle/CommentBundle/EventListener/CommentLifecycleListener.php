<?php

namespace Oro\Bundle\CommentBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Listens to Comment Entity events and generates date stamps
 */
class CommentLifecycleListener
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    public function __construct(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    public function preUpdate(Comment $entity, LifecycleEventArgs $args)
    {
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
     * @param EntityManager $entityManager
     *
     * @return UserInterface|null
     */
    protected function getUser(EntityManager $entityManager)
    {
        $user = $this->tokenAccessor->getUser();

        if (null !== $user
            && $entityManager->getUnitOfWork()->getEntityState($user) === UnitOfWork::STATE_DETACHED
        ) {
            $user = $entityManager->find('OroUserBundle:User', $user->getId());
        }

        return $user;
    }
}
