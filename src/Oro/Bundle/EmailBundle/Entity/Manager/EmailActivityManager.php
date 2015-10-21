<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EmailActivityManager
{
    /**
     * @var ActivityManager
     */
    protected $activityManager;

    /**
     * @var EmailActivityListProvider
     */
    protected $activityListProvider;

    /**
     * @var EmailThreadProvider
     */
    protected $emailThreadProvider;

    /**
     * Emails for updates after flush
     *
     * @var Email[]
     */
    protected $queueUpdate;

    /**
     * @var TokenStorage
     */
    protected $tokenStorage;

    /**
     * @var ServiceLink
     */
    protected $entityOwnerAccessorLink;

    /**
     * @param ActivityManager           $activityManager
     * @param EmailActivityListProvider $activityListProvider
     * @param EmailThreadProvider       $emailThreadProvider
     * @param TokenStorage              $tokenStorage
     * @param ServiceLink               $entityOwnerAccessorLink
     */
    public function __construct(
        ActivityManager $activityManager,
        EmailActivityListProvider $activityListProvider,
        EmailThreadProvider $emailThreadProvider,
        TokenStorage $tokenStorage,
        ServiceLink $entityOwnerAccessorLink
    ) {
        $this->activityManager           = $activityManager;
        $this->emailActivityListProvider = $activityListProvider;
        $this->emailThreadProvider       = $emailThreadProvider;
        $this->resetQueue();
        $this->tokenStorage            = $tokenStorage;
        $this->entityOwnerAccessorLink = $entityOwnerAccessorLink;
    }

    /**
     * Handles onFlush event
     *
     * @param OnFlushEventArgs $event
     */
    public function handleOnFlush(OnFlushEventArgs $event)
    {
        $em  = $event->getEntityManager();
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
                if (count($this->emailActivityListProvider->getTargetEntities($email)) === 0) {
                    $this->addRecipientOwners($targets, $email);
                }
                $this->addSenderOwner($targets, $email);
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
     * {@inheritdoc}
     */
    public function removeActivityTarget(ActivityInterface $activityEntity, $targetEntity)
    {
        return $this->activityManager->removeActivityTarget($activityEntity, $targetEntity);
    }

    /**
     * @param array $targets
     * @param Email $email
     */
    protected function addSenderOwner(&$targets, Email $email)
    {
        $from = $email->getFromEmailAddress();
        if (!$from) {
            return;
        }

        $owner = $from->getOwner();
        if (!$owner) {
            return;
        }

        // @todo: Should be deleted after email sync process will be refactored
        $token = $this->tokenStorage->getToken();
        if ($token) {
            $ownerOrganization = $this->entityOwnerAccessorLink->getService()->getOrganization($owner);
            if ($ownerOrganization
                && $token instanceof OrganizationContextTokenInterface
                && $token->getOrganizationContext()->getId() !== $ownerOrganization->getId()
            ) {
                return;
            }
        }

        $this->addTarget($targets, $owner);
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
     * @param EntityManager $em
     * @param Email         $email
     */
    protected function copyContexts(EntityManager $em, Email $email)
    {
        $thread = $email->getThread();
        if ($thread) {
            $relatedEmails = $em->getRepository(Email::ENTITY_CLASS)->findByThread($thread);
            $contexts      = $this->emailActivityListProvider->getTargetEntities($email);
            // from email to thread emails
            if (count($contexts) > 0) {
                foreach ($relatedEmails as $relatedEmail) {
                    if ($email->getId() !== $relatedEmail->getId()) {
                        $this->changeContexts($em, $relatedEmail, $contexts);
                    }
                }
            } else {
                // from thread to email
                $relatedEmails = $this->emailThreadProvider->getEmailReferences($em, $email);
                if (count($relatedEmails) > 0) {
                    $parentEmail = $relatedEmails[0];
                    $contexts    = $this->emailActivityListProvider->getTargetEntities($parentEmail);
                    $this->changeContexts($em, $email, $contexts);
                }
            }
        }
    }

    /**
     * @param EntityManager $em
     * @param Email         $email
     * @param [] $contexts
     */
    protected function addContextsToThread(EntityManager $em, Email $email, $contexts)
    {
        $thread = $email->getThread();
        if ($thread) {
            $relatedEmails = $em->getRepository(Email::ENTITY_CLASS)->findByThread($thread);
        } else {
            $relatedEmails = [$email];
        }
        if (count($contexts) > 0) {
            foreach ($relatedEmails as $relatedEmail) {
                foreach ($contexts as $context) {
                    $this->addAssociation($relatedEmail, $context);
                }
            }
        }
    }

    /**
     * @param EntityManager $em
     * @param Email         $email
     * @param [] $contexts
     */
    protected function changeContexts(EntityManager $em, Email $email, $contexts)
    {
        $oldContexts    = $this->emailActivityListProvider->getTargetEntities($email);
        //please, do not use array_diff because it compares objects as strings and it is not correct
        $removeContexts = $this->getContextsDiff($oldContexts, $contexts);
        foreach ($removeContexts as $context) {
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

    /**
     * @param array $contexts
     * @param array $anotherContexts
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getContextsDiff(array $contexts, array $anotherContexts)
    {
        $result = [];

        foreach ($contexts as $context) {
            $isPresentInContexts = false;
            foreach ($anotherContexts as $anotherContext) {
                if (is_object($anotherContext) && is_object($context)
                    && get_class($context) === get_class($anotherContext)
                    && $context->getId() === $anotherContext->getId()
                ) {
                    $isPresentInContexts = true;
                } elseif (is_string($anotherContext) && is_string($context) && $anotherContext == $context) {
                    $isPresentInContexts = true;
                }
            }

            if (!$isPresentInContexts) {
                $result[] = $context;
            }
        }

        return $result;
    }
}
