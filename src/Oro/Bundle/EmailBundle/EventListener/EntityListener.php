<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailOwnerAssociationsTopic;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressVisibilityManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailOwnerManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadManager;
use Oro\Bundle\EmailBundle\Provider\EmailOwnersProvider;
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

    private MessageProducerInterface $producer;
    private ContainerInterface $container;
    /** @var Email[] */
    private array $newEmails = [];
    /** @var Email[] */
    private array $updatedEmails = [];
    /** @var Email[] */
    private array $emailsToRemove = [];
    /** @var Email[] */
    private array $emailsToUpdateActivities = [];
    /** @var Email[] */
    private array $emailsToSkipUpdateActivities = [];
    /** @var EmailAddress[] */
    private array $newEmailAddresses = [];
    /** @var EmailAddress[] */
    private array $updatedEmailAddresses = [];
    /** @var string[] */
    private array $processedAddresses = [];

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
    public static function getSubscribedServices(): array
    {
        return [
            EmailOwnerManager::class,
            EmailThreadManager::class,
            EmailActivityManager::class,
            EmailOwnersProvider::class,
            EmailAddressManager::class,
            EmailAddressVisibilityManager::class
        ];
    }

    public function skipUpdateActivities(Email $email): void
    {
        $this->emailsToSkipUpdateActivities[] = $email;
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $em = $event->getObjectManager();
        $uow = $em->getUnitOfWork();

        /** @var EmailOwnerManager $emailOwnerManager */
        $emailOwnerManager = $this->container->get(EmailOwnerManager::class);
        [$updatedAddresses, $newAddresses, $processedAddresses] = $emailOwnerManager->handleChangedAddresses(
            $emailOwnerManager->createEmailAddressData($uow)
        );
        foreach ($updatedAddresses as $emailAddress) {
            $uow->computeChangeSet($em->getClassMetadata(ClassUtils::getClass($emailAddress)), $emailAddress);
        }
        $this->newEmailAddresses = array_merge($this->newEmailAddresses, $newAddresses);
        $this->updatedEmailAddresses = array_merge($this->updatedEmailAddresses, $updatedAddresses);
        $this->processedAddresses = array_merge($this->processedAddresses, $processedAddresses);

        $newEmails = $this->filterEmails($uow->getScheduledEntityInsertions());
        $this->newEmails = array_merge($this->newEmails, $newEmails);
        $this->emailsToUpdateActivities = array_merge($this->emailsToUpdateActivities, $newEmails);

        $this->updatedEmails = array_merge(
            $this->updatedEmails,
            $this->filterEmails($uow->getScheduledEntityUpdates())
        );
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $em = $event->getObjectManager();
        if ($this->newEmails) {
            /** @var EmailThreadManager $emailThreadManager */
            $emailThreadManager = $this->container->get(EmailThreadManager::class);
            $emailThreadManager->updateThreads($this->newEmails);
            $this->newEmails = [];
            $em->flush();
        }
        if ($this->updatedEmails) {
            /** @var EmailThreadManager $emailThreadManager */
            $emailThreadManager = $this->container->get(EmailThreadManager::class);
            $emailThreadManager->updateHeads($this->updatedEmails);
            $this->updatedEmails = [];
            $em->flush();
        }
        if ($this->emailsToUpdateActivities) {
            $this->updateActivities();
            $this->emailsToUpdateActivities = [];
            $this->emailsToSkipUpdateActivities = [];
            $em->flush();
        }

        $this->saveNewEmailAddresses($em);
        $this->addAssociationWithEmailActivity();

        if ($this->emailsToRemove) {
            foreach ($this->emailsToRemove as $email) {
                $em->remove($email);
            }
            $this->emailsToRemove = [];
            $em->flush();
        }

        if ($this->processedAddresses) {
            /** @var EmailAddressVisibilityManager $emailAddressVisibilityManager */
            $emailAddressVisibilityManager = $this->container->get(EmailAddressVisibilityManager::class);
            $emailAddressVisibilityManager->collectEmailAddresses($this->processedAddresses);
            $this->processedAddresses = [];
        }
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        if (!$this->enabled) {
            return;
        }

        $emailUser = $args->getObject();
        if ($emailUser instanceof EmailUser) {
            $email = $emailUser->getEmail();
            if ($email->getEmailUsers()->isEmpty()) {
                $this->emailsToRemove[] = $email;
            }
        }
    }

    private function filterEmails(array $entities): array
    {
        $emails = [];
        foreach ($entities as $key => $entity) {
            if ($entity instanceof Email) {
                $emails[$key] = $entity;
            }
        }

        return $emails;
    }

    private function getEmailOwnersToUpdate(): array
    {
        $owners = array_map(
            function (EmailAddress $emailAddress) {
                return $emailAddress->getOwner();
            },
            $this->updatedEmailAddresses
        );

        /** @var EmailOwnersProvider $emailOwnersProvider */
        $emailOwnersProvider = $this->container->get(EmailOwnersProvider::class);

        return array_filter(
            $owners,
            function (?EmailOwnerInterface $owner) use ($emailOwnersProvider) {
                return $owner && $emailOwnersProvider->hasEmailsByOwnerEntity($owner);
            }
        );
    }

    private function addAssociationWithEmailActivity(): void
    {
        $entities = $this->getEmailOwnersToUpdate();
        $this->updatedEmailAddresses = [];

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

    private function saveNewEmailAddresses(EntityManagerInterface $em): void
    {
        if (!$this->newEmailAddresses) {
            return;
        }

        /** @var EmailAddressManager $emailAddressManager */
        $emailAddressManager = $this->container->get(EmailAddressManager::class);
        $emailAddressRepository = $emailAddressManager->getEmailAddressRepository();
        $hasNewEmailAddresses = false;
        $newEmails = [];
        foreach ($this->newEmailAddresses as $newEmailAddress) {
            $newEmail = $newEmailAddress->getEmail();
            if (!isset($newEmails[$newEmail])) {
                $newEmails[$newEmail] = true;
                if (null === $emailAddressRepository->findOneBy(['email' => $newEmail])) {
                    $em->persist($newEmailAddress);
                    $hasNewEmailAddresses = true;
                }
            }
        }
        $this->newEmailAddresses = [];
        if ($hasNewEmailAddresses) {
            $em->flush();
        }
    }

    private function updateActivities(): void
    {
        if ($this->emailsToSkipUpdateActivities) {
            $emailsToUpdateActivities = [];
            foreach ($this->emailsToUpdateActivities as $email) {
                if (!\in_array($email, $this->emailsToSkipUpdateActivities, true)) {
                    $emailsToUpdateActivities[] = $email;
                }
            }
            if ($emailsToUpdateActivities) {
                /** @var EmailActivityManager $emailActivityManager */
                $emailActivityManager = $this->container->get(EmailActivityManager::class);
                $emailActivityManager->updateActivities($emailsToUpdateActivities);
            }
        } else {
            /** @var EmailActivityManager $emailActivityManager */
            $emailActivityManager = $this->container->get(EmailActivityManager::class);
            $emailActivityManager->updateActivities($this->emailsToUpdateActivities);
        }
    }
}
