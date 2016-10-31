<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\PasswordHash;
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
                $this->createAndPersistPasswordHash($entity, $em);
                $this->resetUserPasswordExpiryDate($entity, $em);
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof User && array_key_exists('password', $uow->getEntityChangeSet($entity))) {
                $this->createAndPersistPasswordHash($entity, $em);
                $this->resetUserPasswordExpiryDate($entity, $em);
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

    /**
     * @param User $user
     * @param EntityManager $em
     */
    public function resetUserPasswordExpiryDate(User $user, EntityManager $em)
    {
        $expiryDate = $this->provider->getPasswordExpiryDateFromNow();
        $user->setPasswordExpiresAt($expiryDate);
        $em->getUnitOfWork()->recomputeSingleEntityChangeSet($em->getClassMetadata(User::class), $user);
    }
}
