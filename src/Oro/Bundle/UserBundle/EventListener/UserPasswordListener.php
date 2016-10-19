<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\UserBundle\Entity\PasswordHash;

class UserPasswordListener
{
    /**
     * @param OnFlushEventArgs $eventArgs
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof UserInterface) {
                $this->createAndPersistPasswordHash($entity, $em);
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof UserInterface && array_key_exists('password', $uow->getEntityChangeSet($entity))) {
                $this->createAndPersistPasswordHash($entity, $em);
            }
        }
    }

    /**
     * @param UserInterface $user
     * @param EntityManager $em
     */
    public function createAndPersistPasswordHash(UserInterface $user, EntityManager $em)
    {
        $salt = $user->getSalt();
        $hash = $user->getPassword();

        /** @var PasswordHash $passwordHash */
        $passwordHash = new PasswordHash();
        $passwordHash->setUser($user);
        $passwordHash->setSalt($salt);
        $passwordHash->setHash($hash);

        $em->persist($passwordHash);
        $em->getUnitOfWork()->computeChangeSet($em->getClassMetadata(PasswordHash::class), $passwordHash);
    }
}
