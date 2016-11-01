<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\PasswordHistory;
use Oro\Bundle\UserBundle\Provider\PasswordChangePeriodConfigProvider;

class UserPasswordListener
{
    /** @var PasswordChangePeriodConfigProvider */
    protected $provider;

    public function __construct(PasswordChangePeriodConfigProvider $provider)
    {
        $this->provider = $provider;
    }
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
                $this->resetUserPasswordExpiryDate($entity, $em);
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof User && array_key_exists('password', $uow->getEntityChangeSet($entity))) {
                $this->createAndPersistPasswordHistory($entity, $em);
                $this->resetUserPasswordExpiryDate($entity, $em);
            }
        }
    }

    /**
     * @param UserInterface $user
     * @param EntityManager $em
     */
    protected function createAndPersistPasswordHistory(UserInterface $user, EntityManager $em)
    {
        $PasswordHistory = new PasswordHistory();
        $PasswordHistory->setUser($user);
        $PasswordHistory->setSalt($user->getSalt());
        $PasswordHistory->setPasswordHash($user->getPassword());

        $em->persist($PasswordHistory);
        $em->getUnitOfWork()->computeChangeSet($em->getClassMetadata(PasswordHistory::class), $PasswordHistory);
    }

    /**
     * @param User $user
     * @param EntityManager $em
     */
    protected function resetUserPasswordExpiryDate(User $user, EntityManager $em)
    {
        $expiryDate = $this->provider->getPasswordExpiryDateFromNow();
        $user->setPasswordExpiresAt($expiryDate);
        $em->getUnitOfWork()->recomputeSingleEntityChangeSet($em->getClassMetadata(User::class), $user);
    }
}
