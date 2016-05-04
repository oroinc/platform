<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

class EmailActivityManager
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var EmailActivityListProvider */
    protected $activityListProvider;

    /** @var EmailThreadProvider */
    protected $emailThreadProvider;

    /** @var TokenStorage */
    protected $tokenStorage;

    /** @var ServiceLink */
    protected $entityOwnerAccessorLink;

    /** @var EntityManager */
    protected $em;

    /**
     * @param ActivityManager           $activityManager
     * @param EmailActivityListProvider $activityListProvider
     * @param EmailThreadProvider       $emailThreadProvider
     * @param TokenStorage              $tokenStorage
     * @param ServiceLink               $entityOwnerAccessorLink
     * @param EntityManager             $em
     */
    public function __construct(
        ActivityManager $activityManager,
        EmailActivityListProvider $activityListProvider,
        EmailThreadProvider $emailThreadProvider,
        TokenStorage $tokenStorage,
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

    public function updateActivities(array $createdEmails)
    {
        foreach ($createdEmails as $email) {
            $this->copyContexts($email);
            // prepare the list of association targets
            $targets = [];
            if (count($this->emailActivityListProvider->getTargetEntities($email)) === 0) {
                $this->addRecipientOwners($targets, $email);
            }
            $this->addSenderOwner($targets, $email);
            // add associations
            $this->addContextsToThread($email, $targets);
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
     * @param Email $email
     */
    protected function copyContexts(Email $email)
    {
        $thread = $email->getThread();
        if ($thread) {
            $relatedEmails = $this->em->getRepository(Email::ENTITY_CLASS)->findByThread($thread);
            $contexts      = $this->emailActivityListProvider->getTargetEntities($email);
            // from email to thread emails
            if (count($contexts) > 0) {
                foreach ($relatedEmails as $relatedEmail) {
                    if ($email->getId() !== $relatedEmail->getId()) {
                        $this->changeContexts($relatedEmail, $contexts);
                    }
                }
            } else {
                // from thread to email
                $relatedEmails = $this->emailThreadProvider->getEmailReferences($this->em, $email);
                if (count($relatedEmails) > 0) {
                    $parentEmail = $relatedEmails[0];
                    $contexts    = $this->emailActivityListProvider->getTargetEntities($parentEmail);
                    $this->changeContexts($email, $contexts);
                }
            }
        }
    }

    /**
     * @param Email         $email
     * @param [] $contexts
     */
    protected function addContextsToThread(Email $email, $contexts)
    {
        $thread = $email->getThread();
        if ($thread) {
            $relatedEmails = $this->em->getRepository(Email::ENTITY_CLASS)->findByThread($thread);
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
     * @param Email         $email
     * @param [] $contexts
     */
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
