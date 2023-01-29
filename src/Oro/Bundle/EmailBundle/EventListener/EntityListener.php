<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailOwnerAssociationsTopic;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressVisibilityManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailOwnerManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadManager;
use Oro\Bundle\EmailBundle\Model\EmailActivityUpdates;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Handles Email entity related changes.
 */
class EntityListener implements OptionalListenerInterface, ServiceSubscriberInterface
{
    use OptionalListenerTrait;

    /** @var  MessageProducerInterface */
    protected $producer;

    /** @var ContainerInterface */
    protected $container;

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

    private $processedAddresses = [];

    public function __construct(
        MessageProducerInterface $producer,
        ContainerInterface $container
    ) {
        $this->producer = $producer;
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_email.email.owner.manager' => EmailOwnerManager::class,
            'oro_email.email.thread.manager' => EmailThreadManager::class,
            'oro_email.email.activity.manager' => EmailActivityManager::class,
            'oro_email.model.email_activity_updates' => EmailActivityUpdates::class,
            'oro_email.email.address.manager' => EmailAddressManager::class,
            'oro_email.email_address_visibility.manager' => EmailAddressVisibilityManager::class
        ];
    }

    public function onFlush(OnFlushEventArgs $event)
    {
        if (!$this->enabled) {
            return;
        }

        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        $emailOwnerManager = $this->getEmailOwnerManager();
        $emailAddressData = $emailOwnerManager->createEmailAddressData($uow);
        [$updatedEmailAddresses, $created, $processedAddresses] = $emailOwnerManager->handleChangedAddresses(
            $emailAddressData
        );
        $this->processedAddresses = array_merge($this->processedAddresses, $processedAddresses);
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

        $this->getEmailActivityUpdates()->processUpdatedEmailAddresses($updatedEmailAddresses);
        $this->newEmailAddresses = array_merge($this->newEmailAddresses, $created);
    }

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

        if ($this->processedAddresses) {
            $this->getEmailAddressVisibilityManager()->collectEmailAddresses($this->processedAddresses);
            $this->processedAddresses = [];
        }
    }

    protected function addAssociationWithEmailActivity(PostFlushEventArgs $event)
    {
        $emailActivityUpdates = $this->getEmailActivityUpdates();
        $entities = $emailActivityUpdates->getFilteredOwnerEntitiesToUpdate();
        $emailActivityUpdates->clearPendingEntities();

        if (!$entities) {
            return;
        }

        $entitiesIdsByClass = [];
        foreach ($entities as $entity) {
            $class = ClassUtils::getClass($entity);
            $entitiesIdsByClass[$class][] = $entity->getId();
        }

        foreach ($entitiesIdsByClass as $class => $ids) {
            $this->producer->send(UpdateEmailOwnerAssociationsTopic::getName(), [
                'ownerClass' => $class,
                'ownerIds' => $ids,
            ]);
        }
    }

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

    protected function saveNewEmailAddresses(EntityManager $em)
    {
        $flush = false;

        $newEmails = [];
        foreach ($this->newEmailAddresses as $newEmailAddress) {
            $newEmail = $newEmailAddress->getEmail();
            if (array_key_exists($newEmail, $newEmails)) {
                continue;
            }
            $newEmails[$newEmail] = true;

            $emailAddress = $this->getEmailAddressManager()
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

    private function getEmailAddressVisibilityManager(): EmailAddressVisibilityManager
    {
        return $this->container->get('oro_email.email_address_visibility.manager');
    }

    protected function getEmailOwnerManager(): EmailOwnerManager
    {
        return $this->container->get('oro_email.email.owner.manager');
    }

    /**
     * @return EmailThreadManager
     */
    protected function getEmailThreadManager()
    {
        return $this->container->get('oro_email.email.thread.manager');
    }

    /**
     * @return EmailActivityManager
     */
    protected function getEmailActivityManager()
    {
        return $this->container->get('oro_email.email.activity.manager');
    }

    protected function getEmailActivityUpdates(): EmailActivityUpdates
    {
        return $this->container->get('oro_email.model.email_activity_updates');
    }

    protected function getEmailAddressManager(): EmailAddressManager
    {
        return $this->container->get('oro_email.email.address.manager');
    }
}
