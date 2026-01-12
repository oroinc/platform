<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Provides a set of methods to simplify managing associations between the Email as the activity entity
 * and other entities this activity is related to.
 */
class EmailActivityManager
{
    private ActivityManager $activityManager;
    private EmailActivityListProvider $emailActivityListProvider;
    private EmailThreadProvider $emailThreadProvider;
    private TokenStorageInterface $tokenStorage;
    private ServiceLink $entityOwnerAccessorLink;
    private ManagerRegistry $doctrine;

    public function __construct(
        ActivityManager $activityManager,
        EmailActivityListProvider $activityListProvider,
        EmailThreadProvider $emailThreadProvider,
        TokenStorageInterface $tokenStorage,
        ServiceLink $entityOwnerAccessorLink,
        ManagerRegistry $doctrine
    ) {
        $this->activityManager = $activityManager;
        $this->emailActivityListProvider = $activityListProvider;
        $this->emailThreadProvider = $emailThreadProvider;
        $this->tokenStorage = $tokenStorage;
        $this->entityOwnerAccessorLink = $entityOwnerAccessorLink;
        $this->doctrine = $doctrine;
    }

    /**
     * @param Email[] $createdEmails
     */
    public function updateActivities(array $createdEmails): void
    {
        foreach ($createdEmails as $createdEmail) {
            $this->updateActivitiesForCreatedEmail($createdEmail);
        }
    }

    public function addAssociation(Email $email, object $context): bool
    {
        return $this->activityManager->addActivityTarget($email, $context);
    }

    public function removeAssociation(Email $email, object $context): bool
    {
        return $this->activityManager->removeActivityTarget($email, $context);
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

    private function addSenderOwner(array &$contexts, Email $email): void
    {
        $from = $email->getFromEmailAddress();
        if (null === $from) {
            return;
        }

        $owner = $from->getOwner();
        if (null !== $owner && $this->isOwnerFromCurrentOrganization($owner)) {
            $contexts[] = $owner;
        }
    }

    private function addRecipientOwners(array &$contexts, Email $email): void
    {
        $recipients = $email->getRecipients();
        foreach ($recipients as $recipient) {
            $owner = $recipient->getEmailAddress()->getOwner();
            if (null !== $owner && $this->isOwnerFromCurrentOrganization($owner)) {
                $contexts[] = $owner;
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
        $referencedEmails = $this->emailThreadProvider->getEmailReferences(
            $this->doctrine->getManagerForClass(Email::class),
            $email
        );
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

    private function isInContext(object $item, array $contexts): bool
    {
        foreach ($contexts as $context) {
            if (
                ClassUtils::getClass($item) === ClassUtils::getClass($context)
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
