<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Core\SecurityContext;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Class EmailManager
 * @package Oro\Bundle\EmailBundle\Entity\Manager
 */
class EmailManager
{
    /**
     * @var EmailThreadManager
     */
    protected $emailThreadManager;

    /**
     * @var EmailThreadProvider
     */
    protected $emailThreadProvider;

    /** @var EntityManager */
    protected $em;

    /**
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * Constructor
     *
     * @param EntityManager       $em                  - Entity Manager
     * @param EmailThreadManager  $emailThreadManager  - Email Thread Manager
     * @param EmailThreadProvider $emailThreadProvider - Email Thread Provider
     * @param SecurityContext     $securityContext     - Security Context
     */
    public function __construct(
        EntityManager $em,
        EmailThreadManager $emailThreadManager,
        EmailThreadProvider $emailThreadProvider,
        SecurityContext $securityContext
    ) {
        $this->em = $em;
        $this->emailThreadManager = $emailThreadManager;
        $this->emailThreadProvider = $emailThreadProvider;
        $this->securityContext = $securityContext;
    }

    /**
     * Set email seen status
     *
     * @param EmailUser $entity - entity
     * @param bool      $value  - value for value filed EmailUser entity
     * @param bool      $flush  - if $flush is true then method executes flush
     *
     * @return void
     */
    public function setEmailUserSeen(EmailUser $entity, $value = true, $flush = false)
    {
        if ($entity->isSeen() !== $value) {
            $entity->setSeen($value);
            if ($flush) {
                $this->em->flush();
            }
        }
    }

    /**
     * @param Email $entity
     * @param bool $checkThread Set statuses for threaded emails
     */
    public function setSeenStatus(Email $entity, $checkThread = false)
    {
        $emails = $this->prepareFlaggedEmailEntities($entity, $checkThread);
        foreach ($emails as $email) {
            $emailUsers = $this->getCurrentEmailUser($email);
            if ($emailUsers) {
                foreach ($emailUsers as $emailUser) {
                    $this->setEmailUserSeen($emailUser, true, true);
                }
            }
        }
    }

    /**
     * @param Email $entity
     * @param bool $checkThread Set statuses for threaded emails
     */
    public function setUnseenStatus(Email $entity, $checkThread = false)
    {
        $emails = $this->prepareFlaggedEmailEntities($entity, $checkThread);
        foreach ($emails as $email) {
            $emailUser = $this->getCurrentEmailUser($email);
            if ($emailUser) {
                $this->setEmailUserSeen($emailUser, false, true);
            }
        }
    }

    /**
     * Toggle user email seen
     *
     * @param EmailUser $entity
     */
    public function toggleEmailUserSeen(EmailUser $entity)
    {
        $seen = !((bool) $entity->isSeen());
        $this->setEmailUserSeen($entity, $seen);
        $this->em->persist($entity);

        if ($entity->getEmail()->getThread() && $entity->getOwner()) {
            $threadedEmailUserBuilder = $this
                ->em
                ->getRepository('OroEmailBundle:EmailUser')
                ->getEmailUserByThreadId([$entity->getEmail()->getThread()->getId()], $entity->getOwner());

            $threadedEmailUserList = $threadedEmailUserBuilder->getQuery()->getResult();
            foreach ($threadedEmailUserList as $threadedEmailUser) {
                $this->setEmailUserSeen($threadedEmailUser, $seen);
                $this->em->persist($threadedEmailUser);
            }
        }

        $this->em->flush();
    }

    /**
     * Mark all email as seen
     *
     * @param User $user
     * @param Organization $organization
     * @return mixed
     */
    public function markAllEmailsAsSeen(User $user, Organization $organization)
    {
        return $this
            ->em
            ->getRepository('OroEmailBundle:EmailUser')
            ->markAllEmailsAsSeen($user, $organization);
    }

    /**
     * @param Email $entity
     * @param $target
     */
    public function addContextToEmailThread(Email $entity, $target)
    {
        $relatedEmails = $this->emailThreadProvider->getThreadEmails($this->em, $entity);
        foreach ($relatedEmails as $relatedEmail) {
            $relatedEmail->addActivityTarget($target);
        }
        $this->em->persist($entity);
        $this->em->flush();
    }

    /**
     * @param Email $entity
     * @param $target
     */
    public function deleteContextFromEmailThread(Email $entity, $target)
    {
        $relatedEmails = $this->emailThreadProvider->getThreadEmails($this->em, $entity);
        foreach ($relatedEmails as $relatedEmail) {
            $relatedEmail->removeActivityTarget($target);
        }
        $this->em->persist($entity);
        $this->em->flush();
    }

    /**
     * Find EmilUser User logged in system
     *
     * @param Email $entity - entity Email
     *
     * @return null|EmailUser
     */
    protected function getCurrentEmailUser(Email $entity)
    {
        $token = $this->securityContext->getToken();
        $user = $token->getUser();
        $currentOrganization = $token->getOrganizationContext();
        $emailUser = $this->em->getRepository('OroEmailBundle:EmailUser')
            ->findByEmailAndOwner($entity, $user, $currentOrganization);

        return $emailUser;
    }

    /**
     * Prepare emails to set status. If need get all from thread
     *
     * @param Email $entity
     * @param bool $checkThread Get threaded emails
     *
     * @return Email[]
     */
    protected function prepareFlaggedEmailEntities(Email $entity, $checkThread)
    {
        $thread = $entity->getThread();
        $emails = [$entity];
        if ($checkThread && $thread) {
            $emails = $thread->getEmails();

            return $emails->toArray();
        }

        return $emails;
    }
}
