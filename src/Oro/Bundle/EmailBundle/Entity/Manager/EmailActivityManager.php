<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;

class EmailActivityManager
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var EmailActivityListProvider */
    protected $activityListProvider;

    /** @var EmailThreadProvider */
    protected $emailThreadProvider;

    /**
     * Emails for updates after flush
     *
     * @var Email[]
     */
    protected $queueUpdate;

    /**
     * @param ActivityManager $activityManager
     * @param EmailActivityListProvider $activityListProvider
     * @param EmailThreadProvider $emailThreadProvider
     */
    public function __construct(
        ActivityManager $activityManager,
        EmailActivityListProvider $activityListProvider,
        EmailThreadProvider $emailThreadProvider
    ) {
        $this->activityManager = $activityManager;
        $this->emailActivityListProvider = $activityListProvider;
        $this->emailThreadProvider = $emailThreadProvider;
    }

    /**
     * Handles onFlush event
     *
     * @param OnFlushEventArgs $event
     */
    public function handleOnFlush(OnFlushEventArgs $event)
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof Email) {
                $this->addEmailToQueue($entity);
            }
        }
    }

    /**
     * Handles postFlush event
     *
     * @param PostFlushEventArgs $event
     */
    public function handlePostFlush(PostFlushEventArgs $event)
    {
        $em = $event->getEntityManager();
        if ($this->getQueue()) {
            foreach ($this->getQueue() as $email) {
                $this->copyContexts($em, $email);
                // prepare the list of association targets
                $targets = [];
                $this->addSenderOwner($targets, $email);
                $this->addRecipientOwners($targets, $email);
                // add associations
                $this->addContextsToThread($em, $email, $targets);
            }
            $this->resetQueue();
            $em->flush();
        }
    }

    /**
     * @param Email  $email
     * @param object $target
     *
     * @return bool TRUE if the association was added; otherwise, FALSE
     */
    public function addAssociation(Email $email, $target)
    {
        return $this->activityManager->addActivityTarget($email, $target);
    }

    /**
     * @param array $targets
     * @param Email $email
     */
    protected function addSenderOwner(&$targets, Email $email)
    {
        $from = $email->getFromEmailAddress();
        if ($from) {
            $owner = $from->getOwner();
            if ($owner) {
                $this->addTarget($targets, $owner);
            }
        }
    }

    /**
     * @param array $targets
     * @param Email $email
     */
    protected function addRecipientOwners(&$targets, Email $email)
    {
        $recipients = $email->getRecipients();
        foreach ($recipients as $recipient) {
            $owner = $recipient->getEmailAddress()->getOwner();
            if ($owner) {
                $this->addTarget($targets, $owner);
            }
        }
    }

    /**
     * @param object[] $targets
     * @param object   $target
     */
    protected function addTarget(&$targets, $target)
    {
        $alreadyExists = false;
        foreach ($targets as $existingTarget) {
            if ($target === $existingTarget) {
                $alreadyExists = true;
                break;
            }
        }
        if (!$alreadyExists) {
            $targets[] = $target;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeActivityTarget(ActivityInterface $activityEntity, $targetEntity)
    {
        return $this->activityManager->removeActivityTarget($activityEntity, $targetEntity);
    }

    /**
     * @param EntityManager $em
     * @param Email $email
     */
    protected function copyContexts(EntityManager $em, Email $email)
    {
        $thread = $email->getThread();
        if ($thread) {
            $relatedEmails = $em->getRepository(Email::ENTITY_CLASS)->findByThread($thread);
            $contexts = $this->emailActivityListProvider->getTargetEntities($email);
            if (count($contexts) > 0) {
                foreach ($relatedEmails as $relatedEmail) {
                    if ($email->getId() !== $relatedEmail->getId()) {
                        $this->changeContexts($em, $relatedEmail, $contexts);
                    }
                }
            } else {
                $relatedEmails = $this->emailThreadProvider->getEmailReferences($em, $email);
                if (count($relatedEmails) > 0) {
                    $parentEmail = $relatedEmails[0];
                    $contexts = $this->emailActivityListProvider->getTargetEntities($parentEmail);
                    $this->changeContexts($em, $email, $contexts);
                }
            }
        }
    }

    /**
     * @param EntityManager $em
     * @param Email $email
     * @param [] $contexts
     */
    protected function addContextsToThread(EntityManager $em, Email $email, $contexts)
    {
        $thread = $email->getThread();
        if ($thread) {
            $relatedEmails = $em->getRepository(Email::ENTITY_CLASS)->findByThread($thread);
            if (count($contexts) > 0) {
                foreach ($relatedEmails as $relatedEmail) {
                    if ($email->getId() !== $relatedEmail->getId()) {
                        foreach ($contexts as $context) {
                            $this->addAssociation($email, $context);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param EntityManager $em
     * @param Email $email
     * @param [] $contexts
     */
    protected function changeContexts(EntityManager $em, Email $email, $contexts)
    {
        $oldContexts = $this->emailActivityListProvider->getTargetEntities($email);
        foreach ($oldContexts as $context) {
            $this->removeActivityTarget($email, $context);
        }
        foreach ($contexts as $context) {
            $this->addAssociation($email, $context);
        }
        $em->persist($email);
    }

    /**
     * Reset thread update queue
     */
    public function resetQueue()
    {
        $this->queueUpdate = [];
    }

    /**
     * Get tread update queue
     *
     * @return Email[]
     */
    public function getQueue()
    {
        return $this->queueUpdate;
    }

    /**
     * Add email to thread update queue
     *
     * @param Email $email
     */
    public function addEmailToQueue(Email $email)
    {
        $this->queueUpdate[] = $email;
    }
}
