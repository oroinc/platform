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
use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;

class EmailManager
{
    /** @var EmailThreadManager */
    protected $emailThreadManager;

    /** @var EmailThreadProvider */
    protected $emailThreadProvider;

    /** @var EntityManager */
    protected $em;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var MailboxManager */
    protected $mailboxManager;

    /**
     * Constructor
     *
     * @param EntityManager $em - Entity Manager
     * @param EmailThreadManager $emailThreadManager - Email Thread Manager
     * @param EmailThreadProvider $emailThreadProvider - Email Thread Provider
     * @param SecurityFacade $securityFacade - Security Facade
     * @param MailboxManager $mailboxManager
     */
    public function __construct(
        EntityManager $em,
        EmailThreadManager $emailThreadManager,
        EmailThreadProvider $emailThreadProvider,
        SecurityFacade $securityFacade,
        MailboxManager $mailboxManager
    ) {
        $this->em                  = $em;
        $this->emailThreadManager  = $emailThreadManager;
        $this->emailThreadProvider = $emailThreadProvider;
        $this->securityFacade      = $securityFacade;
        $this->mailboxManager      = $mailboxManager;
    }

    /**
     * Set seen status for EmailUser
     *
     * @param EmailUser $entity
     * @param bool      $isSeen
     * @param bool      $flush - if true then method executes flush
     */
    public function setEmailUserSeen(EmailUser $entity, $isSeen = true, $flush = false)
    {
        if ($entity->isSeen() !== $isSeen) {
            $entity->setSeen($isSeen);
            if ($flush) {
                $this->em->flush();
            }
        }
    }

    /**
     * Set email seen status for current user for single email or thread
     *
     * @param Email $entity
     * @param bool  $isSeen
     * @param bool  $checkThread - if false it will be applied for single email instead of thread
     */
    public function setSeenStatus(Email $entity, $isSeen = true, $checkThread = false)
    {
        $user         = $this->securityFacade->getLoggedUser();
        $organization = $this->securityFacade->getOrganization();
        $emailUsers   = $this->getEmailUserRepository()
            ->getAllEmailUsersByEmail($entity, $user, $organization, $checkThread);

        foreach ($emailUsers as $emailUser) {
            $this->setEmailUserSeen($emailUser, $isSeen);
        }

        $this->em->flush();
    }

    /**
     * Toggle EmailUser thread seen
     *
     * @param EmailUser $entity
     */
    public function toggleEmailUserSeen(EmailUser $entity)
    {
        $seen = !$entity->isSeen();
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
     * @param User         $user
     * @param Organization $organization
     * @param array        $ids
     *
     * @return boolean
     */
    public function markAllEmailsAsSeen(User $user, Organization $organization, $ids = [])
    {
        $mailboxIds = $this->mailboxManager->findAvailableMailboxIds($user, $organization);

        $emailUserQueryBuilder = $this
            ->em
            ->getRepository('OroEmailBundle:EmailUser')
            ->findUnseenUserEmail($user, $organization, $ids, $mailboxIds);
        $unseenUserEmails = $emailUserQueryBuilder->getQuery()->getResult();

        if (empty($unseenUserEmails)) {
            return false;
        }

        foreach ($unseenUserEmails as $userEmail) {
            $this->setEmailUserSeen($userEmail);
        }

        $this->em->flush();

        return true;
    }

    /**
     * @param Email $entity
     * @param       $target
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
     * @param       $target
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
     * Prepare emails to set status. If need get all from thread
     *
     * @param Email $entity
     * @param bool  $checkThread Get threaded emails
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
