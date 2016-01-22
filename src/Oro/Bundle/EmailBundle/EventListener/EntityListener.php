<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailOwnerManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadManager;
use Oro\Bundle\EmailBundle\Provider\EmailOwnersProvider;

class EntityListener
{
    /** @var EmailOwnerManager */
    protected $emailOwnerManager;

    /** @var EmailActivityManager */
    protected $emailActivityManager;

    /** @var EmailThreadManager */
    protected $emailThreadManager;

    /** @var Email[] */
    protected $emailsToRemove = [];

    /** @var array */
    protected $entitiesOwnedByEmail = [];

    /** @var EmailOwnersProvider */
    protected $emailOwnersProvider;

    /** @var Email[] */
    protected $createdEmails = [];

    /** @var Email = [] */
    protected $activityManagerEmails = [];

    /** @var Email[] */
    protected $updatedEmails = [];

    /**
     * @param EmailOwnerManager $emailOwnerManager
     * @param EmailActivityManager $emailActivityManager
     * @param EmailThreadManager $emailThreadManager
     * @param EmailOwnersProvider $emailOwnersProvider
     */
    public function __construct(
        EmailOwnerManager    $emailOwnerManager,
        EmailActivityManager $emailActivityManager,
        EmailThreadManager   $emailThreadManager,
        EmailOwnersProvider  $emailOwnersProvider
    ) {
        $this->emailOwnerManager    = $emailOwnerManager;
        $this->emailActivityManager = $emailActivityManager;
        $this->emailThreadManager   = $emailThreadManager;
        $this->emailOwnersProvider  = $emailOwnersProvider;
    }

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        $emailAddressData = $this->emailOwnerManager->createEmailAddressData($uow);
        $updatedEmailAddresses = $this->emailOwnerManager->handleChangedAddresses($emailAddressData);
        foreach ($updatedEmailAddresses as $emailAddress) {
             $this->computeEntityChangeSet($em, $emailAddress);
        }

        $createdEmails = array_filter(
            $uow->getScheduledEntityInsertions(),
            $this->getEmailFilter()
        );
        $this->createdEmails = array_merge($this->createdEmails, $createdEmails);
        $this->activityManagerEmails = array_merge($this->activityManagerEmails, $createdEmails);

        $this->updatedEmails = array_merge(
            $this->updatedEmails,
            array_filter(
                $uow->getScheduledEntityUpdates(),
                $this->getEmailFilter()
            )
        );

        $this->addNewEntityOwnedByEmail($uow->getScheduledEntityInsertions());
    }

    /**
     * @param PostFlushEventArgs $event
     */
    public function postFlush(PostFlushEventArgs $event)
    {
        $em = $event->getEntityManager();
        if ($this->createdEmails) {
            $this->emailThreadManager->updateThreads($this->createdEmails);
            $this->createdEmails = [];
            $em->flush();
        }
        if ($this->updatedEmails) {
            $this->emailThreadManager->updateHeads($this->updatedEmails);
            $this->updatedEmails = [];
            $em->flush();
        }
        if ($this->activityManagerEmails) {
            $this->emailActivityManager->updateActivities($this->activityManagerEmails);
            $this->activityManagerEmails = [];
            $em->flush();
        }
        $this->addAssociationWithEmailActivity($event);

        if ($this->emailsToRemove) {
            $em = $event->getEntityManager();

            foreach ($this->emailsToRemove as $email) {
                $em->remove($email);
            }

            $this->emailsToRemove = [];
            $em->flush();
        }
    }

    /**
     * @param array $entities
     */
    protected function addNewEntityOwnedByEmail($entities)
    {
        if ($entities) {
            foreach ($entities as $entity) {
                if ($this->emailOwnersProvider->supportOwnerProvider($entity)) {
                    $this->entitiesOwnedByEmail[] = $entity;
                }
            }
        }
    }

    /**
     * @param PostFlushEventArgs $event
     */
    protected function addAssociationWithEmailActivity(PostFlushEventArgs $event)
    {
        if ($this->entitiesOwnedByEmail) {
            $em = $event->getEntityManager();
            foreach ($this->entitiesOwnedByEmail as $entity) {
                $emails = $this->emailOwnersProvider->getEmailsByOwnerEntity($entity);
                foreach ($emails as $email) {
                    $this->emailActivityManager->addAssociation($email, $entity);
                }
            }
            $this->entitiesOwnedByEmail = [];
            $em->flush();
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $emailUser = $args->getEntity();
        if ($emailUser instanceof EmailUser) {
            $email = $emailUser->getEmail();

            if ($email->getEmailUsers()->isEmpty()) {
                $this->emailsToRemove[] = $email;
            }
        }
    }

    /**
     * @param EntityManager $em
     * @param mixed         $entity
     */
    protected function computeEntityChangeSet(EntityManager $em, $entity)
    {
        $entityClass   = ClassUtils::getClass($entity);
        $classMetadata = $em->getClassMetadata($entityClass);
        $unitOfWork    = $em->getUnitOfWork();
        $unitOfWork->computeChangeSet($classMetadata, $entity);
    }

    /**
     * @return \Closure
     */
    protected function getEmailFilter()
    {
        return function ($entity) {
            return $entity instanceof Email;
        };
    }
}
