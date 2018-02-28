<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailOwnerManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadManager;
use Oro\Bundle\EmailBundle\Model\EmailActivityUpdates;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class EntityListener implements OptionalListenerInterface
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

    /** @var EmailAddress[] */
    protected $newEmailAddresses = [];

    /** @var EmailActivityUpdates */
    protected $emailActivityUpdates;

    /** @var  MessageProducerInterface */
    protected $producer;

    /**
     * @var bool
     */
    protected $enabled = true;
    /**
     * @var EmailAddressManager
     */
    private $emailAddressManager;

    /**
     * @param EmailOwnerManager        $emailOwnerManager
     * @param ServiceLink              $emailActivityManagerLink
     * @param ServiceLink              $emailThreadManagerLink
     * @param EmailActivityUpdates     $emailActivityUpdates
     * @param MessageProducerInterface $producer
     * @param EmailAddressManager      $emailAddressManager
     */
    public function __construct(
        EmailOwnerManager    $emailOwnerManager,
        ServiceLink          $emailActivityManagerLink,
        ServiceLink          $emailThreadManagerLink,
        EmailActivityUpdates $emailActivityUpdates,
        MessageProducerInterface $producer,
        EmailAddressManager $emailAddressManager
    ) {
        $this->emailOwnerManager        = $emailOwnerManager;
        $this->emailActivityManagerLink = $emailActivityManagerLink;
        $this->emailThreadManagerLink   = $emailThreadManagerLink;
        $this->emailActivityUpdates     = $emailActivityUpdates;
        $this->producer = $producer;
        $this->emailAddressManager = $emailAddressManager;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        if (!$this->enabled) {
            return;
        }

        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        $emailAddressData = $this->emailOwnerManager->createEmailAddressData($uow);
        list($updatedEmailAddresses, $created) = $this->emailOwnerManager->handleChangedAddresses($emailAddressData);
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
        $this->newEmailAddresses = array_merge($this->newEmailAddresses, $created);
    }

    /**
     * @param PostFlushEventArgs $event
     */
    public function postFlush(PostFlushEventArgs $event)
    {
        if (!$this->enabled) {
            return;
        }

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

        if ($this->newEmailAddresses) {
            $this->saveNewEmailAddresses($em);
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
        $entities = $this->emailActivityUpdates->getFilteredOwnerEntitiesToUpdate();
        $this->emailActivityUpdates->clearPendingEntities();

        if (!$entities) {
            return;
        }
        
        $entitiesIdsByClass = [];
        foreach ($entities as $entity) {
            $class = ClassUtils::getClass($entity);
            $entitiesIdsByClass[$class][] = $entity->getId();
        }

        foreach ($entitiesIdsByClass as $class => $ids) {
            $this->producer->send(Topics::UPDATE_EMAIL_OWNER_ASSOCIATIONS, [
                'ownerClass' => $class,
                'ownerIds' => $ids,
            ]);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

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

    /**
     * @param EntityManager $em
     */
    private function saveNewEmailAddresses(EntityManager $em)
    {
        $flush = false;

        foreach ($this->newEmailAddresses as $newEmailAddress) {
            $emailAddress = $this->emailAddressManager
                ->getEmailAddressRepository()
                ->findOneBy(['email' => $newEmailAddress->getEmail()]);
            if ($emailAddress === null) {
                $em->persist($newEmailAddress);
                $flush = true;
            }
        }

        $this->newEmailAddresses = [];

        if ($flush) {
            $em->flush();
        }
    }
}
