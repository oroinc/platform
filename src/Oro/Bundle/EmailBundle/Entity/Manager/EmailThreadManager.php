<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;

/**
 * Provides a set of methods to manage data related to email threads.
 */
class EmailThreadManager
{
    private EmailThreadProvider $emailThreadProvider;
    private ManagerRegistry $doctrine;

    public function __construct(EmailThreadProvider $emailThreadProvider, ManagerRegistry $doctrine)
    {
        $this->emailThreadProvider = $emailThreadProvider;
        $this->doctrine = $doctrine;
    }

    /**
     * @param Email[] $newEmails
     */
    public function updateThreads(array $newEmails): void
    {
        $em = $this->getEntityManager();
        foreach ($newEmails as $email) {
            $threadEmails = $this->findThreadEmails($em, $email);
            $thread = $this->findThread($threadEmails);
            if (null === $thread && $threadEmails) {
                $thread = new EmailThread();
                $em->persist($thread);
            }
            if (null !== $thread) {
                $email->setThread($thread);
                foreach ($threadEmails as $threadEmail) {
                    if (null === $threadEmail->getThread()) {
                        $threadEmail->setThread($thread);
                    }
                }
            }
        }
    }

    /**
     * @param Email[] $updatedEmails
     */
    public function updateHeads(array $updatedEmails): void
    {
        $em = $this->getEntityManager();
        foreach ($updatedEmails as $email) {
            if (!$email->getThread() || !$email->getId()) {
                continue;
            }

            $threadEmails = $this->emailThreadProvider->getThreadEmails($em, $email);
            if (!$threadEmails) {
                continue;
            }

            foreach ($threadEmails as $threadEmail) {
                $threadEmail->setHead(false);
            }
            $threadEmails[0]->setHead(true);
        }
    }

    /**
     * @param EntityManagerInterface $em
     * @param Email                  $email
     *
     * @return Email[]
     */
    private function findThreadEmails(EntityManagerInterface $em, Email $email): array
    {
        $threadEmails = $this->emailThreadProvider->getEmailReferences($em, $email);
        if (!$threadEmails) {
            $threadEmails = $this->emailThreadProvider->getReferredEmails($em, $email);
        }

        return $threadEmails;
    }

    /**
     * @param Email[] $threadEmails
     *
     * @return EmailThread|null
     */
    private function findThread(array $threadEmails): ?EmailThread
    {
        $thread = null;
        foreach ($threadEmails as $threadEmail) {
            $thread = $threadEmail->getThread();
            if (null !== $thread) {
                break;
            }
        }

        return $thread;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass(Email::class);
    }
}
