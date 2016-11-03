<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\PasswordHistory;

class PasswordHistoryListener
{
    /**
     * @param OnFlushEventArgs $eventArgs
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof User) {
                $this->createAndPersistPasswordHistory($entity, $em);
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof User && array_key_exists('password', $uow->getEntityChangeSet($entity))) {
                $this->createAndPersistPasswordHistory($entity, $em);
            }
        }
    }

    /**
     * @param UserInterface $user
     * @param EntityManager $em
     */
    protected function createAndPersistPasswordHistory(UserInterface $user, EntityManager $em)
    {
        $passwordHistory = new PasswordHistory();
        $passwordHistory->setUser($user);
        $passwordHistory->setSalt($user->getSalt());
        $passwordHistory->setPasswordHash($user->getPassword());

        $em->persist($passwordHistory);
        $em->getUnitOfWork()->computeChangeSet($em->getClassMetadata(PasswordHistory::class), $passwordHistory);
    }
}
