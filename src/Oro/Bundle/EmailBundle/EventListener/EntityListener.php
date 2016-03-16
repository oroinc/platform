<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailOwnerManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadManager;
use Oro\Bundle\EmailBundle\Model\EmailActivityUpdates;

class EntityListener
{
    /** @var EmailOwnerManager */
    protected $emailOwnerManager;

    /** @var ServiceLink */
    protected $emailActivityManagerLink;

    /** @var ServiceLink */
    protected $emailThreadManagerLink;

    /** @var Email[] */
    protected $emailsToRemove = [];

    /** @var Email[] */
    protected $createdEmails = [];

    /** @var Email[] */
    protected $activityManagerEmails = [];

    /** @var Email[] */
    protected $updatedEmails = [];

    /** @var EmailActivityUpdates */
    protected $emailActivityUpdates;

    /**
     * @param EmailOwnerManager $emailOwnerManager
     * @param ServiceLink $emailActivityManagerLink
     * @param ServiceLink $emailThreadManagerLink
     * @param EmailActivityUpdates $emailActivityUpdates
     */
    public function __construct(
        EmailOwnerManager    $emailOwnerManager,
        ServiceLink          $emailActivityManagerLink,
        ServiceLink          $emailThreadManagerLink,
        EmailActivityUpdates $emailActivityUpdates
    ) {
        $this->emailOwnerManager        = $emailOwnerManager;
        $this->emailActivityManagerLink = $emailActivityManagerLink;
        $this->emailThreadManagerLink   = $emailThreadManagerLink;
        $this->emailActivityUpdates     = $emailActivityUpdates;
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

        $this->emailActivityUpdates->processUpdatedEmailAddresses($updatedEmailAddresses);
    }

    /**
     * @param PostFlushEventArgs $event
     */
    public function postFlush(PostFlushEventArgs $event)
    {
        $em = $event->getEntityManager();
        if ($this->createdEmails) {
            $this->getEmailThreadManager()->updateThreads($this->createdEmails);
            $this->createdEmails = [];
            $em->flush();
        }
        if ($this->updatedEmails) {
            $this->getEmailThreadManager()->updateHeads($this->updatedEmails);
            $this->updatedEmails = [];
            $em->flush();
        }
        if ($this->activityManagerEmails) {
            $this->getEmailActivityManager()->updateActivities($this->activityManagerEmails);
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
     * @param PostFlushEventArgs $event
     */
    protected function addAssociationWithEmailActivity(PostFlushEventArgs $event)
    {
        $jobs = $this->emailActivityUpdates->createJobs();
        if (!$jobs) {
            return;
        }

        $em = $event->getEntityManager();
        array_map([$em, 'persist'], $jobs);
        $em->flush();
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

    /**
     * @return EmailThreadManager
     */
    public function getEmailThreadManager()
    {
        return $this->emailThreadManagerLink->getService();
    }

    /**
     * @return EmailActivityManager
     */
    protected function getEmailActivityManager()
    {
        return $this->emailActivityManagerLink->getService();
    }
}
