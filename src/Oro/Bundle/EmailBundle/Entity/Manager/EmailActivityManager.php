<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Provides a set of methods to simplify managing associations between the Email as the activity entity
 * and other entities this activity is related to.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EmailActivityManager
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var EmailActivityListProvider */
    protected $emailActivityListProvider;

    /** @var EmailThreadProvider */
    protected $emailThreadProvider;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var ServiceLink */
    protected $entityOwnerAccessorLink;

    /** @var EntityManager */
    protected $em;

    public function __construct(
        ActivityManager $activityManager,
        EmailActivityListProvider $activityListProvider,
        EmailThreadProvider $emailThreadProvider,
        TokenStorageInterface $tokenStorage,
        ServiceLink $entityOwnerAccessorLink,
        EntityManager $em
    ) {
        $this->activityManager           = $activityManager;
        $this->emailActivityListProvider = $activityListProvider;
        $this->emailThreadProvider       = $emailThreadProvider;
        $this->tokenStorage            = $tokenStorage;
        $this->entityOwnerAccessorLink = $entityOwnerAccessorLink;
        $this->em                      = $em;
    }

    /**
     * @param Email[] $createdEmails
     */
    public function updateActivities(array $createdEmails)
    {
        foreach ($createdEmails as $createdEmail) {
            $this->updateActivitiesForCreatedEmail($createdEmail);
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

    private function updateActivitiesForCreatedEmail(Email $email): void
    {
        $contexts = $this->emailActivityListProvider->getTargetEntities($email);
        if (\count($contexts) > 0) {
            return;
        }

        $contextsToAdd = [];

        $this->addSenderOwner($contextsToAdd, $email);
        $this->addRecipientOwners($contextsToAdd, $email);

        $thread = $email->getThread();
        if (null !== $thread) {
            // add contexts that added manually to referenced emails to this email
            $referencedCustomContexts = $this->getCustomContextsOfReferencedEmails($email);
            if ($referencedCustomContexts) {
                $contextsToAdd = $referencedCustomContexts;
            }
        }

        $addedContexts = [];
        foreach ($contextsToAdd as $context) {
            if (!$this->isInContext($context, $addedContexts)) {
                $this->addAssociation($email, $context);
                $addedContexts[] = $context;
            }
        }
    }

    /**
     * @param array $targets
     * @param Email $email
     */
    protected function addSenderOwner(&$targets, Email $email)
    {
        $from = $email->getFromEmailAddress();
        if (null === $from) {
            return;
        }

        $owner = $from->getOwner();
        if (null !== $owner && $this->isOwnerFromCurrentOrganization($owner)) {
            $targets[] = $owner;
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
            if (null !== $owner && $this->isOwnerFromCurrentOrganization($owner)) {
                $targets[] = $owner;
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

    protected function copyContexts(Email $email)
    {
        $thread = $email->getThread();
        if ($thread) {
            $contexts = $this->emailActivityListProvider->getTargetEntities($email);
            if (count($contexts) > 0) {
                // from email to thread emails
                $relatedEmails = $this->em->getRepository(Email::class)->findByThread($thread);
                foreach ($relatedEmails as $relatedEmail) {
                    if ($email->getId() !== $relatedEmail->getId()) {
                        $this->changeContexts($relatedEmail, $contexts);
                    }
                }
            } else {
                // add contexts that added manually to referenced emails to this email
                $referencedCustomContexts = $this->getCustomContextsOfReferencedEmails($email);
                if (!empty($referencedCustomContexts)) {
                    $this->changeContexts($email, $referencedCustomContexts);
                }
            }
        }
    }

    /**
     * Returns contexts of all referenced emails excluding contexts
     * related to senders and recipients of these emails.
     * It means that only contexts added manually will be returned.
     */
    private function getCustomContextsOfReferencedEmails(Email $email): array
    {
        $customContextsForAllReferencedEmails = [];
        $referencedEmails = $this->emailThreadProvider->getEmailReferences($this->em, $email);
        foreach ($referencedEmails as $referencedEmail) {
            $sendersAndRecipients = [];
            $this->addRecipientOwners($sendersAndRecipients, $referencedEmail);
            $this->addSenderOwner($sendersAndRecipients, $referencedEmail);
            $referencedEmailContexts = $this->emailActivityListProvider->getTargetEntities($referencedEmail);
            foreach ($referencedEmailContexts as $context) {
                if (!$this->isInContext($context, $sendersAndRecipients)) {
                    $customContextsForAllReferencedEmails[] = $context;
                }
            }
        }

        return $customContextsForAllReferencedEmails;
    }

    protected function addContextsToThread(Email $email, $contexts)
    {
        $relatedEmails = [$email];
        if (count($contexts) > 0) {
            foreach ($relatedEmails as $relatedEmail) {
                foreach ($contexts as $context) {
                    $this->addAssociation($relatedEmail, $context);
                }
            }
        }
    }

    protected function changeContexts(Email $email, $contexts)
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
        $this->em->persist($email);
    }

    /**
     * Returns all items from $contexts that do not exist in $anotherContexts.
     *
     * @param array $contexts
     * @param array $anotherContexts
     *
     * @return array
     */
    public function getContextsDiff(array $contexts, array $anotherContexts)
    {
        $result = [];
        foreach ($contexts as $context) {
            if (!$this->isInContext($context, $anotherContexts)) {
                $result[] = $context;
            }
        }

        return $result;
    }

    private function isInContext(object $item, array $contexts): bool
    {
        foreach ($contexts as $context) {
            if (ClassUtils::getClass($item) === ClassUtils::getClass($context)
                && $item->getId() === $context->getId()
            ) {
                return true;
            }
        }

        return false;
    }

    private function isOwnerFromCurrentOrganization(object $owner): bool
    {
        $token = $this->tokenStorage->getToken();
        if (!$token instanceof OrganizationAwareTokenInterface) {
            return true;
        }

        $ownerOrganization = $this->entityOwnerAccessorLink->getService()->getOrganization($owner);
        if (null === $ownerOrganization) {
            return true;
        }

        return $ownerOrganization->getId() === $token->getOrganization()->getId();
    }
}
