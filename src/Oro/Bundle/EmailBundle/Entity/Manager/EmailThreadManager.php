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
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Email::class);
        foreach ($newEmails as $email) {
            $threadEmails = $this->emailThreadProvider->getEmailReferences($em, $email);
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
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Email::class);
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

    private function findThread(array $threadEmails): ?EmailThread
    {
        $thread = null;
        /** @var Email $threadEmail */
        foreach ($threadEmails as $threadEmail) {
            $thread = $threadEmail->getThread();
            if (null !== $thread) {
                break;
            }
        }

        return $thread;
    }
}
