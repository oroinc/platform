<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailUserRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Manager for EmailUser entity
 */
class EmailManager
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private EmailThreadProvider $emailThreadProvider,
        private TokenAccessorInterface $tokenAccessor,
        private MailboxManager $mailboxManager
    ) {
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
                $this->getEntityManager()->flush();
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
        $user = $this->tokenAccessor->getUser();
        $organization = $this->tokenAccessor->getOrganization();
        $emailUsers = $this->getEmailUserRepository()
            ->getAllEmailUsersByEmail($entity, $user, $organization, $checkThread);

        foreach ($emailUsers as $emailUser) {
            $this->setEmailUserSeen($emailUser, $isSeen);
        }

        $this->getEntityManager()->flush();
    }

    /**
     * Toggle EmailUser thread seen
     */
    public function toggleEmailUserSeen(EmailUser $entity)
    {
        $seen = !$entity->isSeen();
        $this->setEmailUserSeen($entity, $seen);
        $em = $this->getEntityManager();
        $em->persist($entity);

        if ($entity->getEmail()->getThread() && $entity->getOwner()) {
            $threadedEmailUserBuilder = $this->getEmailUserRepository()
                ->getEmailUserByThreadId([$entity->getEmail()->getThread()->getId()], $entity->getOwner());

            $threadedEmailUserList = $threadedEmailUserBuilder->getQuery()->getResult();
            foreach ($threadedEmailUserList as $threadedEmailUser) {
                $this->setEmailUserSeen($threadedEmailUser, $seen);
                $em->persist($threadedEmailUser);
            }
        }

        $em->flush();
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

        $emailUserQueryBuilder = $this->getEmailUserRepository()
            ->findUnseenUserEmail($user, $organization, $ids, $mailboxIds);
        $unseenUserEmails = $emailUserQueryBuilder->getQuery()->getResult();

        if (empty($unseenUserEmails)) {
            return false;
        }

        foreach ($unseenUserEmails as $userEmail) {
            $this->setEmailUserSeen($userEmail);
        }

        $this->getEntityManager()->flush();

        return true;
    }

    public function addContextToEmailThread(Email $entity, $target)
    {
        $em = $this->getEntityManager();
        $relatedEmails = $this->emailThreadProvider->getThreadEmails($em, $entity);
        foreach ($relatedEmails as $relatedEmail) {
            $relatedEmail->addActivityTarget($target);
        }
        $em->persist($entity);
        $em->flush();
    }

    public function deleteContextFromEmailThread(Email $entity, $target)
    {
        $em = $this->getEntityManager();
        $relatedEmails = $this->emailThreadProvider->getThreadEmails($em, $entity);
        foreach ($relatedEmails as $relatedEmail) {
            $relatedEmail->removeActivityTarget($target);
        }
        $em->persist($entity);
        $em->flush();
    }

    /**
     * Gets emails by ids
     *
     * @param int[] $ids
     *
     * @return Email[]
     */
    public function findEmailsByIds($ids)
    {
        return $this->getEntityManager()->getRepository(Email::class)->findEmailsByIds($ids);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass(EmailUser::class);
    }

    private function getEmailUserRepository(): EmailUserRepository
    {
        return $this->getEntityManager()->getRepository(EmailUser::class);
    }
}
