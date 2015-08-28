<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailUserRepository;

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
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * Constructor
     *
     * @param EntityManager       $em                  - Entity Manager
     * @param EmailThreadManager  $emailThreadManager  - Email Thread Manager
     * @param EmailThreadProvider $emailThreadProvider - Email Thread Provider
     * @param SecurityFacade      $securityFacade      - Security Facade
     */
    public function __construct(
        EntityManager $em,
        EmailThreadManager $emailThreadManager,
        EmailThreadProvider $emailThreadProvider,
        SecurityFacade $securityFacade
    ) {
        $this->em = $em;
        $this->emailThreadManager = $emailThreadManager;
        $this->emailThreadProvider = $emailThreadProvider;
        $this->securityFacade = $securityFacade;
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
            $emailUsers = $this->getCurrentEmailUser($email);
            if ($emailUsers) {
                foreach ($emailUsers as $emailUser) {
                    $this->setEmailUserSeen($emailUser, false, true);
                }
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
     *
     * @return boolean
     */
    public function markAllEmailsAsSeen(User $user, Organization $organization)
    {
        $emailUserQueryBuilder = $this
            ->em
            ->getRepository('OroEmailBundle:EmailUser')
            ->findUnseenUserEmail($user, $organization);
        $unseenUserEmails = $emailUserQueryBuilder->getQuery()->getResult();

        foreach ($unseenUserEmails as $userEmail) {
            $this->setEmailUserSeen($userEmail);
        }

        if (count($unseenUserEmails) > 0) {
            $this->em->flush();

            return true;
        }

        return false;
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
     * @return EmailUser[]
     */
    protected function getCurrentEmailUser(Email $entity)
    {
        $user = $this->securityFacade->getToken()->getUser();
        $currentOrganization = $this->securityFacade->getOrganization();

        return array_merge(
            $this->getEmailUserRepository()->findByEmailAndOwner($entity, $user, $currentOrganization),
            $this->getEmailUserRepository()->findByEmailForMailbox($entity)
        );
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

    /**
     * @return EmailUserRepository
     */
    protected function getEmailUserRepository()
    {
        return $this->em->getRepository('OroEmailBundle:EmailUser');
    }
}
