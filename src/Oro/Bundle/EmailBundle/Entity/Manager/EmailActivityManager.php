<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Provides a set of methods to simplify managing associations between the Email as the activity entity
 * and other entities this activity is related to.
 */
class EmailActivityManager
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var EmailActivityListProvider */
    protected $emailActivityListProvider;

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

    /**
     * @param array $createdEmails
     */
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
            $contexts = $this->emailActivityListProvider->getTargetEntities($email);
            if (count($contexts) > 0) {
                // from email to thread emails
                $relatedEmails = $this->em->getRepository(Email::ENTITY_CLASS)->findByThread($thread);
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
     *
     * @param Email $email
     *
     * @return array
     */
    private function getCustomContextsOfReferencedEmails(Email $email)
    {
        $referencedContexts = [];
        $referencedEmails = $this->emailThreadProvider->getEmailReferences($this->em, $email);
        foreach ($referencedEmails as $referencedEmail) {
            $sendersAndRecipients = [];
            $this->addRecipientOwners($sendersAndRecipients, $referencedEmail);
            $this->addSenderOwner($sendersAndRecipients, $referencedEmail);
            $allContexts = $this->emailActivityListProvider->getTargetEntities($referencedEmail);
            $customContexts = $this->getContextsDiff($allContexts, $sendersAndRecipients);

            $referencedContexts = array_merge($referencedContexts, $customContexts);
        }

        return $referencedContexts;
    }

    /**
     * @param Email         $email
     * @param [] $contexts
     */
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

    /**
     * Checks if $needle exists in $haystack.
     *
     * @param mixed $needle
     * @param array $haystack
     *
     * @return bool
     */
    private function isInContext($needle, array $haystack)
    {
        foreach ($haystack as $haystackItem) {
            if ($this->areContextsEqual($needle, $haystackItem)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if two context items are equal.
     *
     * @param mixed $item1
     * @param mixed $item2
     *
     * @return bool
     */
    private function areContextsEqual($item1, $item2)
    {
        if (is_object($item1)
            && is_object($item2)
            && ClassUtils::getClass($item1) === ClassUtils::getClass($item2)
            && $item1->getId() === $item2->getId()
        ) {
            return true;
        }
        if (is_string($item1)
            && is_string($item2)
            && $item1 === $item2
        ) {
            return true;
        }

        return false;
    }
}
